<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'ffmpeg:i2v {--i=*} {--dir=} {--bgm=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // 每张图片展示时长(含转场时间)
        $showTime = 3;
        // 转场动效时长
        $duration = 1;

        $images = $this->option('i'); // 绝对路径
        $dir = $this->option('dir'); // 绝对路径
        $bgm = $this->option('bgm'); // 绝对路径
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
        }

        if (count($imageList) < 2) {
            $this->error('jpeg图片数量小于2');
            return;
        }

        // 输出文件
        $fileMd5 = md5(implode(',', $imageList));
        $outputFileName = storage_path('ai_videos'). '/' . $fileMd5 .'.mp4';

        // ' ffmpeg -loop 1 -t 2 -i test1.jpeg -loop 1 -t 2 -i test2.jpeg -loop 1 -t 2 -i test3.jpeg -filter_complex "[0][1]xfade=transition=fade:duration=1:offset=2,format=yuv420p[fade1];[fade1][2]xfade=transition=fade:duration=1:offset=4,format=yuv420p" -y output.mp4';
        // 指令拼接
        $inputStr = $filterComplexStr = '';

        // 倒序处理，保证图在前边（不然有问题）
        foreach (array_reverse($imageList) as $index => $image) {
            if (!Str::endsWith($image, 'jpeg')) {
                $this->warn('非jpeg图片将被忽略：'. $image);
                continue;
            }
            if (!Str::startsWith($image, '/')) {
                $image = $dir . '/' . $image;
            }

            $inputStr .=  sprintf(" -loop 1 -t %s -i %s", $index == 0 ? $showTime -1 : $showTime, $image);
            if ($index > 0) {
                $filterComplexStr .= sprintf('[%s][%s]xfade=transition=fade:duration=%d:offset=%s,format=yuv420p[%s];', $index, $index == 1 ? '0' : 'tmp'.($index-1), $duration, $showTime - $duration, 'tmp'.$index);
            }
        }
        if (empty($inputStr)) {
            $this->error('图片列表为空');
            return;
        }

        // 合入背景音乐
        $inputStr .= ' -i ' . $bgm;
        $filterComplexStr .= sprintf("[%d:a]atrim=0:%s,asetpts=PTS-STARTPTS[outa];", count($imageList), count($imageList) * ($showTime - $duration));

        $command = sprintf('ffmpeg %s -filter_complex "%s" -map "[%s]" -map "[outa]" -y %s', $inputStr, $filterComplexStr, 'tmp'.(count($imageList) - 1), $outputFileName);
        $this->info($command);
        exec($command, $output, $res);
        if ($res === 0 ) {
            return $outputFileName;
        }
    }
}
