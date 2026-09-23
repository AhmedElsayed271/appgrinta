<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Exception;

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Exception $e
     * @return Response
     * @throws Throwable
     */
    public function render($request, $e): Response
    {
        if ($e instanceof ValidationException){
            return $this->convertValidationExceptionToResponse($e,$request);
        }
        if ($e instanceof ModelNotFoundException){
            $modelName=strtolower(class_basename($e->getModel()));
            return $this->errorResponse("Does not exists any {$modelName} with  specified identification",404);
        }
        if ($e instanceof AuthenticationException){
            return $this->unauthenticated($request,$e);
        }
        if ($e instanceof AuthorizationException){
            return $this->errorResponse($e->getMessage(),403);
        }
        if ($e instanceof NotFoundHttpException){
            return $this->errorResponse('The specified URL can\'t be found',404);
        }
        if ($e instanceof MethodNotAllowedHttpException){
            return $this->errorResponse('The specified method for this request is invalid',405);
        }
        if ($e instanceof HttpException){
            return $this->errorResponse($e->getMessage(),$e->getStatusCode());
        }
        if ($e instanceof QueryException){
            $errorCode=$e->errorInfo[1];
            if ($errorCode === 1451){
                return $this->errorResponse('can\'t this resource permanently.it related with any other resource',409);
            }
            return $this->errorResponse($e->getMessage(),409);
        }
        if ($e instanceof TokenMismatchException){
            return redirect()->back()->withInput($request->input());
        }
        if (config('app.debug')){
            return parent::render($request, $e);
        }
        return $this->errorResponse('Unexpected Exception. Try later',500);
    }

    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors=$e->validator->errors()->getMessages();
        if ($this->isFrontend($request)){
            return $request->ajax() ? response()->json($errors[array_key_first($errors)][0],422) : redirect()
                ->back()->withInput($request->input())->withErrors($errors);
        }
        return $this->errorResponse($errors[array_key_first($errors)][0],422);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->isFrontend($request)){
            return redirect()->guest('login');
        }
        return $this->errorResponse('unauthenticated.',401);
    }

    private function isFrontend(Request $request): bool
    {
        return $request->acceptsHtml() && collect($request->route()->middleware())->contains('web');
    }
}
