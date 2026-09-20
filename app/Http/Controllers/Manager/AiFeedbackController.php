<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AiLearningFeedback;
use Illuminate\Http\Request;

class AiFeedbackController extends Controller
{
    /**
     * Display the continuous active learning feedback ledger.
     */
    public function index()
    {
        $feedbacks = AiLearningFeedback::with('user')->latest()->paginate(10);
        $totalTunedKeywords = AiLearningFeedback::where('is_applied', true)->count();

        return view('manager.ai-feedback', compact('feedbacks', 'totalTunedKeywords'));
    }

    /**
     * Toggle the enforcement status of a specific learned keyword weight.
     */
    public function toggle($id)
    {
        $feedback = AiLearningFeedback::findOrFail($id);
        $feedback->is_applied = !$feedback->is_applied;
        $feedback->save();

        return redirect()->back()->with('success', "Learning feedback ID #{$feedback->id} status updated.");
    }
}