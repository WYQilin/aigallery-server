<?php

namespace App\Http\Controllers;

use App\Jobs\ImagesMergeVideo;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    /**
     * 图片列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request) {
        $query = Image::select(['id', 'height', 'width', 'modify_time', 'modify_date', 'file_path']);
        // 过滤条件：日期筛选
        $date = $request->input('date');
        if (!empty($date)) {
            $query = $query->where('modify_date', '=', $date);
        }
        $images = $query->orderBy('modify_time', 'desc')->paginate(10);
        return $images;
    }

    /**
     * 合集列表 todo 单独建表
     * @param Request $request
     * @return mixed
     */
    public function collections(Request $request) {
        // 获取分组数据，并按时间排序
        $result = Image::select(['prompt_hash', 'modify_time', 'width', 'height'])->groupBy('prompt_hash')->orderBy('modify_time', 'desc')->paginate(5);
        $items = $result->getCollection();
        // 获取分组下的图片数据
        $images = Image::select(['id', 'file_path', 'prompt_hash', 'prompt'])->whereIn('prompt_hash', $items->pluck('prompt_hash'))->get();
        // 按prompt hash分组
        $groupList = $images->groupBy('prompt_hash');
        $collections = [];
        foreach ($items as $key => $item) {
            $collectionData =  [
                'group_id' => $item['prompt_hash'],
                'images' => $groupList->get($item['prompt_hash'])->toArray(),
                'modify_time' => $item['modify_time'],
                'height' => $item['height'],
                'width' => $item['width'],
            ];
            $options = Image::getOptions($collectionData['images'][0]['prompt']);
            $collectionData['options'] = $options;
            $collections[] = $collectionData;
        }
        // 回填列表数据
        $result->setCollection(collect($collections));
        return $result;
    }

    /**
     * 贡献热力图数据
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContributionData()
    {
        $dateCount = DB::table('images')->select(DB::raw('DATE(modify_time) as date'), DB::raw('count(*) as count'))->groupBy('date')->orderBy('date')->get();
        if (empty($dateCount)) {
            return response()->json(['contributionData' => []]);
        }
        $dateCount = $dateCount->pluck('count', 'date')->toArray();
        // 获取当前日期，保留到当周周末
        $endDate = Carbon::now()->endOfWeek();
        // 获取一年前的日期，保留到当周周一
        $startDate = $endDate->copy()->subYear()->startOfWeek();

        // 初始化周数
        $contributionData = $monthData = [];

        // 循环生成每一天的数据
        for ($date = $endDate; $date->gte($startDate); $date->subDay()) {
            // 每周的第一天（周一）
            if ($date->isSunday() || empty($contributionData)) {
                $contributionData[] = [
                    'days' => [],
                ];
                $monthData[] = '';
            }
            if ($date->day === 1) {
                // 每月的第一天，重置月份数据
                $monthData[count($monthData) - 1] = $date->month . '月';
            }

            // 生成每日数据
            $dateStr = $date->toDateString();
            $count = $dateCount[$dateStr] ?? 0;
            $contributionData[count($contributionData) - 1]['days'][] = [
                'count' => $count,
                'day' => $date->day,
                'date' => $dateStr,
                'date_w' => $this->formatDateText($date), // 含星期的日期
                'level' => $this->getLevel($count), // 根据贡献次数生成等级
                'hide' => (int)$date->startOfDay()->gt(Carbon::now()), // 隐藏未来的日期
            ];
        }

        return response()->json([
            'contribution_data' => $contributionData,
            'month_data' => $monthData,
            'total' => array_sum($dateCount),
        ]);
    }

    /**
     * 格式化日期文本
     */
    private function formatDateText(Carbon $date): string
    {
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;
        $weekday = $date->isoFormat('dddd');
        return "{$year}年{$month}月{$day}日 {$weekday}";
    }

    /**
     * 根据贡献次数生成等级
     */
    private function getLevel(int $count): string
    {
        if ($count === 0) {
            return 'level-0';
        } elseif ($count <= 10) {
            return 'level-1';
        } elseif ($count <= 20) {
            return 'level-2';
        } elseif ($count <= 50) {
            return 'level-3';
        } else {
            return 'level-4';
        }
    }

    /**
     * 多张图片合成幻灯片视频
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function imagesMergeVideo(Request $request)
    {
        $images = $request->input('images');
        if (empty($images)) {
            return response()->json(['message' => '图片不能为空'], 400);
        }
        // 创建异步任务(默认是同步执行，可自行调整配置)
        dispatch(new ImagesMergeVideo($images));
        return response()->json(['message' => '任务已提交']);
    }
}
