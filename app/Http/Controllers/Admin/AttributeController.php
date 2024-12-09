<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = Attribute::query()->latest('id')->paginate(5);
            return response()->json([
                'message' => 'Danh sách attribute trang ' . request('page', 1),
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeRequest $request)
    {
        try {
            $data = $request->all();
            $data['slug'] = Str::slug($request->name);

            $exists = Attribute::where('slug', $data['slug'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Slug đã tồn tại, vui lòng thử lại',
                    'status' => 'error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $attribute = Attribute::query()->create($data);

            return response()->json([
                'message' => 'Thêm mới attribute thành công',
                'status' => 'success',
                'data' => $attribute
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {

            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Thêm attribute thất bại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Attribute $attribute)
    {
        try {
            $data=Attribute::findOrFail($attribute->id);
            return response()->json([
                'message' => 'Lấy chi tiết attribute thành công',
                'status' => 'success',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Không tìm thấy attribute',
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
        }

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attribute $attribute)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttributeRequest $request, $attribute)
    {
        try {
            $model = Attribute::findOrFail($attribute);

            $data =$request->all();
            $data['slug'] = Str::slug($request->name);

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $attribute)
    {
        try {
            $model = Attribute::with(['attributeValues.variants'])->findOrFail($attribute->id);
            // Log::info($model->attributeValues);
            foreach ($model->attributeValues as  $value) {

                if($value->variants){
                    return response()->json([
                        'messages' => 'Không thể xóa biến thể sản phẩm vì vẫn đang tồn tại trong hệ thống. Vui lòng kiểm tra và đảm bảo không có liên kết nào trước khi thực hiện thao tác này.',
                        'status' => 'error',
                    ], Response::HTTP_BAD_REQUEST);
                }
            }


            $model->delete();

            return response()->json([
                'messages' => 'Xóa attribute thành công',
                'status' => 'success',
                'data' => $model
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



    public function getAttributeDetail($id)
    {
        try {
            $attribute = Attribute::with('attributeValues')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $attribute,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute không tồn tại.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("không tìm thấy id $id: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attribute.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function updateType(Request $request, $id)
    {
        // Xác thực yêu cầu (type phải nằm trong danh sách hợp lệ)
        $validated = $request->validate([
            'type' => 'required|in:select,color,button,radio', // Kiểm tra type hợp lệ
        ]);

        try {
            $attribute = Attribute::findOrFail($id);

            $attribute->type = $validated['type'];
            $attribute->save();
            return response()->json([
                'success' => true,
                'message' => 'Attribute type updated successfully.',
                'data' => $attribute,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type value.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error("Error updating attribute type with id $id: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attribute type.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // Mã lỗi 500
        }
    }






}
