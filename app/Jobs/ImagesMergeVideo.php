<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImagesMergeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $images;
    protected $notify;
    /**
     * Create a new job instance.
     * @param array $images 图片列表
     * @param bool $notify 是否发送机器人通知
     * @return void
     */
    public function __construct(array $images, bool $notify = false)
    {
        $this->images = $images;
        $this->notify = $notify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $exitCode = Artisan::call('ffmpeg:i2v', ['--i' => $this->images, '--gif' => true]);
        Log::info($exitCode);
        $output = Artisan::output();
        Log::info($output);
        if ($this->notify) {
            preg_match('/outputGifName:(.*\.gif)/', $output, $match);
            $gifFile = $match[1] ?? '';
            if (empty($gifFile) || !file_exists($gifFile)) {
                Log::error('生成GIF失败，通知中断');
                return;
            }
//            // 读取图片文件内容
//            $imageData = file_get_contents($gifFile);
//            // 将图片数据转换为 Base64 编码
//            $base64Image = base64_encode($imageData);
//            Notice::ruliuNotice([
//                'IMAGE' => $base64Image,
//                'TEXT' => PHP_EOL . Str::afterLast($gifFile, '/')
//            ]);
        }
    }

}
