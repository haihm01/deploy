<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::latest('id')->paginate(5);
        return BannerResource::collection($banners);
    }

    public function store(StoreBannerRequest $request)
{
    try {
        $banner = Banner::create($request->validated());

        return response()->json([
            'message' => 'Banner đã được thêm thành công!',
            'banner' => new BannerResource($banner)
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Có lỗi xảy ra trong quá trình thêm Banner.',
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function show($id)
    {
        $banner = Banner::findOrFail($id);

        return new BannerResource($banner);
    }

    public function update(UpdateBannerRequest $request, $id)
{
    try {
        $banner = Banner::findOrFail($id);

        $banner->update($request->validated());

        return response()->json([
            'message' => 'Banner đã được sửa thành công!',
            'banner' => new BannerResource($banner)
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Banner không được tìm thấy.',
            'message' => $e->getMessage()
        ], 404);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Có lỗi xảy ra trong quá trình cập nhật Banner.',
            'message' => $e->getMessage()
        ], 500);
    }
}


public function updateIsActive(UpdateBannerRequest $request, $id)
{
    try {
        $banner = Banner::findOrFail($id);

        $banner->update(['is_active' => $request->validated()['is_active']]);

        return response()->json([
            'message' => 'Banner trạng thái đã được cập nhật thành công!',
            'banner' => new BannerResource($banner)
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Banner không được tìm thấy.',
            'message' => $e->getMessage()
        ], 404);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Có lỗi xảy ra trong quá trình cập nhật trạng thái Banner.',
            'message' => $e->getMessage()
        ], 500);
    }
}


public function destroy($id)
{
    try {
        $banner = Banner::findOrFail($id);

        $banner->delete();

        return response()->json(['message' => 'Banner đã được xóa thành công!'], 200);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Xóa banner thất bại', 'error' => $e->getMessage()], 500);
    }
}
// public function createBannerMultiple(StoreBannerRequest $request)
// {
//     try {
//         $banner = Banner::create([
//             'image' => json_encode($request->images),
//             'is_active' => $request->is_active ?? true,
//         ]);

//         return response()->json([
//             'message' => 'Banner đã được thêm thành công!',
//             'banner' => new BannerResource($banner)
//         ], 201);

//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => 'Có lỗi xảy ra trong quá trình thêm Banner.',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }
// public function updateBannerMultiple(StoreBannerRequest $request, $id)
// {
//     try {
//         $banner = Banner::findOrFail($id);

//         $banner->update([
//             'image' => json_encode($request->images),
//             'is_active' => $request->is_active ?? $banner->is_active,
//         ]);

//         return response()->json([
//             'message' => 'Banner đã được cập nhật thành công!',
//             'banner' => new BannerResource($banner)
//         ], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => 'Có lỗi xảy ra trong quá trình cập nhật Banner.',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

}
