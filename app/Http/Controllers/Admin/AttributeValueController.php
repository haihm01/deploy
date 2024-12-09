<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeValue\StoreAttributeValueRequest;
use App\Http\Requests\AttributeValue\UpdateAttributeValueRequest;
use App\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;


class AttributeValueController extends Controller
{
    public function index()
    {
        try {
            $data = AttributeValue::with('attribute')
            ->latest('id')
            ->paginate(5);
            return response()->json([
                'message' => 'Danh sách' . request('page', 1),
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
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
    public function getByAttributeId(string $id): JsonResponse
    {
        try {
            $attributeValues = AttributeValue::where('attribute_id', $id)->get();

            if ($attributeValues->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy thuộc tính với attribute_id này.',
                ], 404);
            }

            return response()->json([
                'attribute_id' => $id,
                'attribute_values' => $attributeValues,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi truy xuất thuộc tính.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(StoreAttributeValueRequest $value)
    {
        try {
          
            $data =$value->all();
    
            // Tạo bản ghi mới
            $attributeValue = AttributeValue::create($data);
    
            // Nạp mối liên hệ 'attribute' để hiển thị thêm
            $attributeValue->load('attribute');
    
            return response()->json([
                'message' => 'Thêm mới Attribute value thành công',
                'status' => 'success',
                'data' => $attributeValue
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
    
            return response()->json([
                'message' => 'Thêm attribute value thất bại',
                'status' => 'error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update(UpdateAttributeValueRequest $request, $attributeValue)
    {
        try {
            
            $data = $request->all();
            $model = AttributeValue::query()->findOrFail($attributeValue);
            $model->update($data);


            return response()->json([
                'data' => $model,
                'status' => 'success',
                'messages' =>  'Cập nhật attribute thành công'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Cập nhật attribute thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $model = AttributeValue::query()->findOrFail($id);
            if(!$model){
                return response()->json([
                    'status' => 'error',
                    'messages' =>  'Vui lòng thử lại'
                ], Response::HTTP_NOT_FOUND);
            }

            $model->delete();

            return response()->json([
                'messages' => 'Xóa attribute thành công',
                'status' => $model
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Xóa attribute thất bại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}