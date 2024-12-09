<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faq\FaqRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function listQuestions()
    {
        $questions = Question::orderByDesc('id')->paginate(10);
        return response()->json($questions);
    }

    public function listAnswers()
    {
        $answers = Answer::orderByDesc('id')->paginate(10);
        return response()->json($answers);
    }

    public function storeQuestions(FaqRequest $request)
    {
        $question = Question::create(
            [
                'text' => $request->text,
                'answer_id' => $request->answer_id
            ]
        );

        return response()->json([
            'message' => 'Tạo câu hỏi thành công.',
            'question' => $question
        ]);
    }

    public function storeAnswers(FaqRequest $request)
    {
        $answer = Answer::create(
            [
                'text' => $request->text,
                'question_id' => $request->question_id
            ]
        );

        return response()->json([
            'message' => 'Tạo câu trả lời thành công.',
            'answer' => $answer
        ]);
    }

    public function deleteQuestion(string $id)
    {
        $question = Question::findOrFail($id);

        $question->delete();
        return response()->json(['message' => 'Xóa câu hỏi thành công!'], 200);
    }

    public function deleteAnswer(string $id)
    {
        $answer = Answer::findOrFail($id);

        $answer->delete();
        return response()->json(['message' => 'Xóa câu trả lời thành công!'], 200);
    }
}
