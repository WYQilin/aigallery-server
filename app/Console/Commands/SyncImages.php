<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @options date 图片日期，默认为当天。示例：2025-01-01';
     * @options all 是否同步所有图片，默认为否。';
     */
    protected $signature = 'sync:images {--date=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步目录中的图片信息到数据库中。';

    /**
     * 允许的图片扩展名类型
     * @var string[]
     */
    protected $extensionTypes = [
        'jpeg', // jpeg压缩效果好，图片体积小，建议画图产出此类型，节省系统带宽
//        'png', // png压缩效果差，图片体积大，exif读取不便，暂不支持 @todo
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dir = storage_path('ai_images'); // 图片存放目录
        if (empty($dir) || !is_dir($dir)) {
            $this->error('图片目录不存在，请检查配置。AI_IMAGES_DIR:'.$dir);
            return;
        }
        $this->info('开始处理图片目录：' . $dir);
        $imageList = $this->getAllJpegFiles($dir);
        Log::info('图片数量：' . count($imageList));
        if (empty($imageList)) {
            $this->info('没有找到图片，结束处理。');
            return;
        }
        $addSuccessNum = 0;
        foreach ($imageList as $key => $file) {
            $imageInfo = $this->getExifData($file);
            if (empty($imageInfo)) {
                continue;
            }
            // 保存图片
            $fileContent = File::get($file);
            $fileName = md5($fileContent).'.'.pathinfo($file, PATHINFO_EXTENSION);
            if (!Storage::exists($fileName)) {
                Storage::put($fileName, $fileContent);
            }
            $imageInfo['file_path'] = $fileName;
            if (!empty(\App\Models\Image::where('file_path', $fileName)->first())) {
                continue;
            }
            $image = new \App\Models\Image($imageInfo);
            if ($image->save()) {
                Log::info('图片保存成功：' . $file);
                $addSuccessNum++;
            } else {
                Log::error('图片保存失败：' . $file);
            }
        }
        $this->info('图片处理完成。新增图片数：'.$addSuccessNum);
    }

    /**
     * 获取 jpeg 文件列表
     * @param string $directory 绝对路径
     * @return array
     */
    private function getAllJpegFiles(string $directory): array
    {
        $jpegFiles = [];

        // 检查目录是否存在
        if (!File::exists($directory)) {
            return [];
        }

        // 使用递归遍历目录及子目录
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $this->extensionTypes)) {
                $jpegFiles[] = $file->getRealPath();
            }
        }

        return $jpegFiles;
    }

    /**
     * 获取图片的exif信息
     * @param string $imagePath
     * @return array|false
     */
    private function getExifData(string $imagePath)
    {
        $exifData = exif_read_data($imagePath, 0, true);
        $data = $this->transformData($exifData);
        return $data;
    }

    /**
     * 转换exif数据格式，提取需要的
     * @param $exifData
     * @return array|false
     */
    private function transformData($exifData)
    {
        $prompt = mb_convert_encoding(substr($exifData['EXIF']['UserComment'], 8), 'UTF-8', 'UTF-16');
        // 用于将相同参数的图片归为一组
        $promptHash = substr(hash('sha256', $prompt), 0, 8);
        // 优先从文件名提取（stable diffusion可以设置prompt hash到文件名，可以考虑优先使用）
//        if (preg_match('/[\-\_](.{8,9}).jpeg$/', $exifData['FILE']['FileName'], $matches) === 1) {
//            $promptHash = $matches[1];
//        }
        $data = array(
            'prompt' => $prompt,
            'height' => $exifData['COMPUTED']['Height'],
            'width' => $exifData['COMPUTED']['Width'],
            'size' => $exifData['FILE']['FileSize'],
            'modify_time' => date('Y-m-d H:i:s', $exifData['FILE']['FileDateTime']),
            'modify_date' => date('Y-m-d', $exifData['FILE']['FileDateTime']),
            'prompt_hash' => $promptHash,
        );
        return $data;
    }

}
