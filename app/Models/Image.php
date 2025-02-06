<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $guarded = [];
    protected $appends = ['url', 'thumb'];
    protected $hidden = ['file_path'];

    /**
     * 图片链接
     * @return string
     */
    public function getUrlAttribute() {
        return Storage::url($this->attributes['file_path']);
    }

    /**
     * 缩略图链接（可调整缩略图地址，以节省带宽）
     * @return string
     */
    public function getThumbAttribute() {
//        return Storage::disk('thumb')->url($this->attributes['file_path']);
        return Storage::url($this->attributes['file_path']);
    }

    /**
     * 从图片prompt文本中提取参数信息
     * @param $text
     * @return array
     */
    public static function getOptions($text) {
        $patterns = [
            'positive_prompt' => '/^(.*?)\s*Negative prompt: /s',
            'negative_prompt' => '/Negative prompt: ([^,]+)/',
            'steps' => '/Steps: (\d+)/',
            'sampler' => '/Sampler: (\w+)/',
            'schedule_type' => '/Schedule type: (\w+)/',
            'cfg_scale' => '/CFG scale: ([\d.]+)/',
            'seed' => '/Seed: (\d+)/',
            'size' => '/Size: (\d+x\d+)/',
            'model_hash' => '/Model hash: ([a-z0-9]+)/',
            'model' => '/Model: ([^,]+)/',
            'clip_skip' => '/Clip skip: (\d+)/',
            'adetailer_model' => '/ADetailer model: ([^,]+)/',
            'adetailer_confidence' => '/ADetailer confidence: ([\d.]+)/',
            'adetailer_dilate_erode' => '/ADetailer dilate erode: (\d+)/',
            'adetailer_mask_blur' => '/ADetailer mask blur: (\d+)/',
            'adetailer_denoising_strength' => '/ADetailer denoising strength: ([\d.]+)/',
            'adetailer_inpaint_only_masked' => '/ADetailer inpaint only masked: (\w+)/',
            'adetailer_inpaint_padding' => '/ADetailer inpaint padding: (\d+)/',
            'adetailer_version' => '/ADetailer version: ([\d.]+)/',
            'postprocess_upscale_by' => '/Postprocess upscale by: (\d+)/',
            'postprocess_upscaler' => '/Postprocess upscaler: ([^,]+)/',
            'version' => '/Version: ([\w.]+)/'
        ];

        $results = [];
        // 依次正则匹配数据
        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $text, $matches);
            if (isset($matches[1])) {
                $results[$key] = trim($matches[1]);
            }
        }
        return $results;
    }
}
