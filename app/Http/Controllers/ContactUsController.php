<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUsRequest;
use App\Repositories\Eloquents\ContactUsRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Contact", description="Contact us")
 */
class ContactUsController extends Controller
{
    public $repository;

    public function __construct(ContactUsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Post(
     *   path="/api/contact-us",
     *   tags={"Contact"},
     *   summary="Contact us",
     *   @OA\Response(response=200, description="Submitted")
     * )
     */
    public function contactUs(ContactUsRequest $request)
    {
        return $this->repository->contactUs($request);
    }
}
