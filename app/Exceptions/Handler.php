<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please log in again.',
                ], 419);
            }

            return redirect()->route('login')->with('warning', 'Your session expired. Please log in again.');
        });

        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419 || $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session expired. Please log in again.',
                    ], 419);
                }

                return redirect()->route('login')->with('warning', 'Your session expired. Please log in again.');
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof \Illuminate\Session\TokenMismatchException || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 419)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please log in again.',
                ], 419);
            }

            return redirect()->route('login')->with('warning', 'Your session expired. Please log in again.');
        }

        return parent::render($request, $e);
    }
}
