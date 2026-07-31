<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionAndAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminQuestionAnswerController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'unanswered');
        $search = $request->get('search', '');

        $query = QuestionAndAnswer::with(['consumer', 'product.product_thumbnail'])
            ->whereNull('deleted_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'ILIKE', "%{$search}%")
                  ->orWhere('answer', 'ILIKE', "%{$search}%")
                  ->orWhereHas('consumer', fn($u) => $u->where('name', 'ILIKE', "%{$search}%"))
                  ->orWhereHas('product', fn($p) => $p->where('name', 'ILIKE', "%{$search}%"));
            });
        }

        if ($tab === 'unanswered') {
            $query->whereNull('answer');
        }

        $questions = $query->latest('created_at')->paginate(30)->withQueryString();

        $unansweredCount = QuestionAndAnswer::whereNull('answer')->whereNull('deleted_at')->count();

        return view('admin.questions.index', compact('questions', 'unansweredCount', 'tab', 'search'));
    }

    public function answer(Request $request, $id)
    {
        $request->validate(['answer' => 'required|string|max:5000']);

        $qna = QuestionAndAnswer::findOrFail($id);
        DB::table('question_and_answers')->where('id', $id)->update([
            'answer'     => $request->answer,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Answer saved successfully.');
    }

    public function destroy($id)
    {
        QuestionAndAnswer::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Question deleted.');
    }
}
