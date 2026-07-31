<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Raines Africa E-Commerce API",
 *   version="1.0.2",
 *   description="Comprehensive e-commerce API for Raines Africa platform. Features: Multi-vendor marketplace, Product variations, Layby payment plans, Seller registration, Advanced filtering, Elasticsearch integration, and Mobile app support.",
 *   @OA\Contact(
 *     email="dev@raines.africa",
 *     name="Raines Africa API Support"
 *   ),
 *   @OA\License(
 *     name="Proprietary",
 *     url="https://raines.africa/terms"
 *   )
 * )
 *
 * @OA\Tag(
 *   name="Layby",
 *   description="Layby application and payment management. Apply for layby payment plans, check eligibility, make payments."
 * )
 *
 * @OA\Tag(
 *   name="Stores",
 *   description="Vendor store management and seller registration. Create vendor accounts with complete business details."
 * )
 *
 * @OA\Server(
 *   url=L5_SWAGGER_CONST_HOST,
 *   description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Enter your Bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *   schema="Error",
 *   type="object",
 *   title="Error Response",
 *   @OA\Property(property="error", type="string", example="Error message"),
 *   @OA\Property(property="code", type="integer", example=422),
 *   @OA\Property(
 *     property="details",
 *     type="object",
 *     @OA\Property(property="field_name", type="array", @OA\Items(type="string", example="Validation error message"))
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="Success",
 *   type="object",
 *   title="Success Response",
 *   @OA\Property(property="success", type="boolean", example=true),
 *   @OA\Property(property="message", type="string", example="Operation successful"),
 *   @OA\Property(property="data", type="object", description="Response data")
 * )
 *
 * @OA\Schema(
 *   schema="PaginatedResponse",
 *   type="object",
 *   title="Paginated Response",
 *   @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="current_page", type="integer", example=1),
 *   @OA\Property(property="last_page", type="integer", example=10),
 *   @OA\Property(property="per_page", type="integer", example=20),
 *   @OA\Property(property="total", type="integer", example=195),
 *   @OA\Property(property="from", type="integer", example=1),
 *   @OA\Property(property="to", type="integer", example=20)
 * )
 */
final class ApiDocs extends Controller
{
    // This class contains only OpenAPI documentation annotations.
    // Individual endpoint documentation is in their respective controller files.
}
