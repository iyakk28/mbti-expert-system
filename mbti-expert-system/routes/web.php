<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;

// Landing Page
Route::get('/', [QuizController::class, 'landing'])->name('landing');

// Start Quiz
Route::post('/start-quiz', [QuizController::class, 'startQuiz'])->name('start.quiz');

// Quiz Questions
Route::get('/quiz/{session}/question/{question_number}', [QuizController::class, 'showQuestion'])
     ->name('quiz.question');

// Save Answer
Route::post('/quiz/{session}/answer', [QuizController::class, 'saveAnswer'])
     ->name('quiz.answer');

// Show Result
Route::get('/quiz/{session}/result', [QuizController::class, 'showResult'])
     ->name('quiz.result');

// Download Result PDF
Route::get('/quiz/{session}/download-pdf', [QuizController::class, 'downloadResultPDF'])
     ->name('quiz.download-pdf');

// Send Result
Route::post('/quiz/{session}/send-result', [QuizController::class, 'sendResult'])
     ->name('send.result');

// Send PDF to WhatsApp
Route::post('/quiz/send-pdf-whatsapp', [QuizController::class, 'sendPdfWhatsapp'])
     ->name('quiz.send-pdf-whatsapp');


     // Fallback
Route::fallback(function () {
    return redirect()->route('landing');
});