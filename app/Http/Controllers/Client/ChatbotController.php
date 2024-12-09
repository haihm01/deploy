<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function index()
    {
        $questions = Question::all();
        return response()->json($questions);
    }

    public function getAnswers($id)
    {
        try {
            $question = Question::with('answers')->find($id);

            if (!$question) {
                return response()->json(['message' => 'Không tìm thấy câu hỏi này.'], 404);
            }

            return response()->json([
                'question' => $question,
                'answers' => $question->answers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi: ' . $e->getMessage()], 500);
        }
    }



    public function checkForMatches(Request $request)
    {
        try {
            $queryText = $request->input('text');

            $question = Question::where('text', 'LIKE', "%$queryText%")->first();

            if (!$question) {
                return response()->json(['message' => 'Không tìm thấy câu hỏi nào trùng khớp.'], 404);
            }

            $answers = Answer::all();
            // $answers = Answer::where('question_id', $question->id)->get();

            return response()->json([
                'question' => $question,
                'answers' => $answers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi: ' . $e->getMessage()], 500);
        }
    }
}
