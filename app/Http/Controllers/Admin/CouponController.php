<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;


class CouponController extends Controller
{
    public function index()
    {
        try {
            $data = Coupon::query()->latest('id')->paginate(10);

            return response()->json([
                'message' => 'Danh sách coupon trang ' . request('page', 1),
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $th) {

            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Lỗi tải trang',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCouponRequest $request)
    {
        try {
            $coupon = Coupon::create($request->validated());

            return response()->json([
                'message' => 'Thêm mới coupon thành công',
                'status' => 'success',
                'data' => $coupon
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {

            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Thêm coupon thất bại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $model = Coupon::findOrFail($id);

            return response()->json([
                'data' => $model,
                'messages' => 'Chi tiết coupon',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Vui lòng thử lại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, string $id)
    {
        try {
            $data = $request->all();

            $model = Coupon::query()->findOrFail($id);

            $model->update($data);

            return response()->json([
                'data' => $model,
                'status' => 'success',
                'messages' =>  'Cập nhật coupon thành công'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Cập nhật coupon thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $model = Coupon::findOrFail($id);
            $model->delete();

            return response()->json([
                'messages' => 'Xóa coupon thành công',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Xóa coupon thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $coupon = Coupon::findOrFail($id);

            $coupon->update([
                'status' => $request->status,
            ]);
            return response()->json([
                'message' => 'Cập nhật trạng thái hoạt động thành công!',
                'data' => $coupon
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'messages' => 'Cập nhật coupon thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function updateIsFeatured(Request $request, string $id)
    {
        try {
            $coupon = Coupon::findOrFail($id);

            $coupon->update([
                'is_featured' => $request->is_featured,
            ]);
            return response()->json([
                'message' => 'Cập nhật trạng thái hoạt động thành công!',
                'data' => $coupon
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'messages' => 'Cập nhật coupon thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}