<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateSettingRequest;
use App\Repositories\Eloquents\SettingRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Settings", description="Global settings")
 */
class SettingController extends Controller
{
    public $repository;

    public function __construct(SettingRepository $repository)
    {
        $this->repository = $repository;
    }


    public function index(Request $request)
    {
        //return $this->repository->latest('created_at')->first();
        return $this->respondWithSettings($request);
    }

    /**
     * @OA\Get(
     *   path="/api/settingsFront",
     *   tags={"Settings"},
     *   summary="Settings (front)",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function indexFront(Request $request){
        return $this->respondWithSettings($request);
    }

    /**
     * Builds the settings payload as a plain array so mobile-specific type
     * coercion (see Helpers::coerceSettingsForMobile) doesn't round-trip
     * through Setting's values accessor/mutator, which would rebuild
     * default_currency from scratch and undo the coercion.
     */
    private function respondWithSettings(Request $request)
    {
        $data = getCachedSettings()->toArray();

        if (Helpers::isMobileClient($request->userAgent())) {
            $data['values'] = Helpers::coerceSettingsForMobile($data['values']);
        }

        Helpers::logMobilePayload($request->path(), $request->userAgent(), $data);

        return response()->json($data);
    }

    /**
     * @OA\Get(
     *   path="/api/settings",
     *   tags={"Settings"},
     *   summary="Front settings (backend route)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function frontSettings(Request $request)
    {
        $settings = $this->repository->frontSettings();

        if (Helpers::isMobileClient($request->userAgent())) {
            $settings['values'] = Helpers::coerceSettingsForMobile($settings['values']);
        }

        Helpers::logMobilePayload($request->path(), $request->userAgent(), $settings);

        return $settings;
    }

    /**
     * @OA\Put(
     *   path="/api/settings",
     *   tags={"Settings"},
     *   summary="Update settings",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        return $this->repository->update($request->all(), null);
    }
}
