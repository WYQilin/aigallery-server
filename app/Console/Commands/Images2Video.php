<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 多图拼接视频
 */
class Images2Video extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ffmpeg:i2v {--i=*} {--dir=} {--bgm=} {--gif} {--transition=fade}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '多图拼接视频生成命令';

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
        Log::info('多图拼接视频生成命令执行');
        // 每张图片展示时长(含转场时间)
        $showTime = 3;
        // 转场动效时长
        $duration = 1;

        $images = $this->option('i'); // 绝对路径
        $dir = $this->option('dir'); // 绝对路径
        $bgm = $this->option('bgm'); // 绝对路径
        $transition = $this->option('transition') ?: ''; // 转场动画效果

        Log::info('图片列表：'. implode(',', $images));
        if (empty($bgm) || !file_exists($bgm)) {
            $bgm = storage_path('bgm.mp4'); // 默认背景音乐
        }
        if (!empty($images)) {
            $imageList = $images;
        } else {
            if (empty($dir)) {
                $this->error('请输入图片或有效目录');
                return;
            }
            $files = scandir($dir);
            $imageList = array_filter($files, function ($file) {
                return Str::endsWith($file, 'jpeg');
            });
            foreach ($imageList as &$image) {
                $image = $dir . '/' . $image;
            }
        }

        if (count($imageList) < 2) {
            $this->error('jpeg图片数量小于2');
            return;
        }

        // 输出文件
        $fileMd5 = md5(implode(',', $imageList));
        $fileName = $fileMd5 .'.mp4';
        $outputFileName = storage_path('ai_videos'). '/' . $fileName;
        if (file_exists($outputFileName)) {
            $this->warn('文件已存在，将覆盖：'. $outputFileName);
        }

        // 网络下载太慢，检查本地文件，如存在则优先使用，提高速度。
        if (Storage::getDefaultDriver() == 'public') {
            foreach ($imageList as $index => &$image) {
                // url连接检查文件名是否在本地
                $localFileName = basename($image);
                if (Str::startsWith($image, 'http') && Storage::fileExists($localFileName)) {
                    $imageList[$index] = Storage::path($localFileName);
                }
            }
        }
        // 获取首图尺寸
        $firstImage = $imageList[0];
        list($firstWidth, $firstHeight) = getimagesize($firstImage);

        // 服务器内存2G较小，缩小后处理。性能好可以不设置
        $maxWidth = 512; // 若首图宽度>512则等比缩放。
        if (!empty($maxWidth)) {
            $targetWidth = min($firstWidth, 512);
            $scaleRatio = $targetWidth / $firstWidth;
            $targetHeight = (int) round($firstHeight * $scaleRatio);
        } else {
            $targetWidth = $firstWidth;
            $targetHeight = $firstHeight;
        }

        // ' ffmpeg -loop 1 -t 2 -i test1.jpeg -loop 1 -t 2 -i test2.jpeg -loop 1 -t 2 -i test3.jpeg -filter_complex "[0][1]xfade=transition=fade:duration=1:offset=2,format=yuv420p[fade1];[fade1][2]xfade=transition=fade:duration=1:offset=4,format=yuv420p" -y output.mp4';
        // 指令拼接
        $inputStr = $filterComplexStr = '';

        // 倒序处理，保证图在前边（不然有问题）
        $imageList = array_reverse($imageList);
        foreach ($imageList as $index => $image) {
            if (!Str::endsWith($image, 'jpeg')) {
                $this->warn('非jpeg图片将被忽略：'. $image);
                continue;
            }

            $inputStr .=  sprintf(" -loop 1 -t %s -i %s", $index == 0 ? $showTime -1 : $showTime, $image);
            if ($index > 0) {
                $filterComplexStr .= sprintf('[%s]scale=%s:%s[scale%s];[%s]scale=%s:%s[scale%s];[scale%s][scale%s]xfade=transition=%s:duration=%d:offset=%s,format=yuv420p[%s];',
                    $index, $targetWidth, $targetHeight, $index,
                    $two = ($index == 1 ? '0' : 'tmp'.($index-1)), $targetWidth, $targetHeight, $two,
                    $index, $two,
                    $transition,
                    $duration,
                    $showTime - $duration,
                    'tmp'.$index
                );
            }
        }
        if (empty($inputStr)) {
            $this->error('图片列表为空');
            return;
        }

        // 合入背景音乐
        $inputStr .= ' -i ' . $bgm;
        $filterComplexStr .= sprintf("[%d:a]atrim=0:%s,asetpts=PTS-STARTPTS[outa];", count($imageList), count($imageList) * ($showTime - $duration));

        $command = sprintf('ffmpeg -thread_queue_size 1024 %s -filter_complex "%s" -map "[%s]" -map "[outa]" -y %s', $inputStr, $filterComplexStr, 'tmp'.(count($imageList) - 1), $outputFileName);
        $this->info($command);
        // 执行命令
        exec($command, $output, $res);

        // 是否生成gif缩略图
        $outputGifName = '';
        if ($this->option('gif')) {
            $outputGifName = preg_replace('/\.mp4$/', '.gif', $outputFileName);
            exec('ffmpeg -thread_queue_size 1024 -i '.$outputFileName.' -vf "fps=10,scale=160:-1:flags=lanczos" -gifflags +transdiff -y ' . $outputGifName, $output, $res);
        }

        // 执行结果
        if ($res === 0) {
            $this->info('视频生成成功');
            $this->info('outputMp4Name:'.$outputFileName);
            $this->info('outputGifName:'.$outputGifName);
            if (!Storage::put(basename($outputFileName), file_get_contents($outputFileName))) {
                $this->error('视频文件存储失败');
                return;
            }
            $video = new Video();
            $video->file_name = $fileName;
            $video->file_path = $outputFileName;
            $video->poster = $outputGifName;
            $video->save();
            $this->info('视频保存完成');
        } else {
            $this->error('执行失败');
        }
    }
}
