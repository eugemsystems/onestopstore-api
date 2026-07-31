<?php

namespace App\Http\Controllers;

use Exception;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\QuestionAndAnswer;
use App\Http\Requests\FeedbackRequest;
use Illuminate\Database\Eloquent\Builder;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\QuestionAndAnswerRequest;
use App\Repositories\Eloquents\QuestionAndAnswerRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Q&A", description="Questions and Answers")
 */
class QuestionAndAnswerController extends Controller
{
    protected $repository;

    public function __construct(QuestionAndAnswerRepository $repository)
    {
        $this->authorizeResource(QuestionAndAnswer::class, 'question_and_answer', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/question-and-answer",
     *   tags={"Q&A"},
     *   summary="List Q&A",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $questionAndAnswer = $this->filter($this->repository, $request);
            return $questionAndAnswer->latest('created_at')->paginate($request->paginate ?? $questionAndAnswer->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }

    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/question-and-answer",
     *   tags={"Q&A"},
     *   summary="Create Q&A",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(QuestionAndAnswerRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/question-and-answer/{question_and_answer}",
     *   tags={"Q&A"},
     *   summary="Get Q&A",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="question_and_answer", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(QuestionAndAnswer $questionAndAnswer)
    {
        return $this->repository->show($questionAndAnswer->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/question-and-answer/{question_and_answer}",
     *   tags={"Q&A"},
     *   summary="Update Q&A",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="question_and_answer", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(QuestionAndAnswerRequest $request, QuestionAndAnswer $questionAndAnswer)
    {
        return $this->repository->update($request->all(), $questionAndAnswer->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/question-and-answer/{question_and_answer}",
     *   tags={"Q&A"},
     *   summary="Delete Q&A",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="question_and_answer", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, QuestionAndAnswer $questionAndAnswer)
    {
        return  $this->repository->destroy($questionAndAnswer->getId($request));
    }

    /**
     * @OA\Post(
     *   path="/api/question-and-answer/feedback",
     *   tags={"Q&A"},
     *   summary="Q&A feedback",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function feedback(FeedbackRequest $request)
    {
        return  $this->repository->feedback($request);
    }

    public function filter($questionAndAnswers, $request)
    {
        $questionAndAnswers = $questionAndAnswers->whereNotNull('answer');
        if ($request->field && $request->sort) {
            $questionAndAnswers = $questionAndAnswers->orderBy($request->field, $request->sort);
        }

        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            $questionAndAnswers = $this->repository;
            if ($roleName == RoleEnum::CONSUMER && $request->product_id) {
                $questionAndAnswers = $this->repository->where('consumer_id',Helpers::getCurrentUserId())
                    ->where('product_id',$request->product_id)->orWhereNotNull('answer');
            }

            if ($roleName == RoleEnum::VENDOR) {
                $questionAndAnswers  = $questionAndAnswers->where('store_id', Helpers::getCurrentVendorStoreId());
            }
        }

        if ($request->product_id) {
            $questionAndAnswers = $questionAndAnswers->where('product_id',$request->product_id);
        }

        if ($request->product_slug) {
            $product_slug = $request->product_slug;
            $questionAndAnswers = $questionAndAnswers->whereHas('products', function (Builder $products) use ($product_slug) {
                $products->whereSlug($product_slug);
            });
        }

        return $questionAndAnswers;
    }
}
