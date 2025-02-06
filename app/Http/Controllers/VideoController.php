<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * 图片列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request) {
        $videos = Video::select('*')->orderBy('id', 'desc')->paginate(10);
        return $videos;
    }

}
