<?php

namespace App\Http\Controllers;

use App\Domain\Breadcrumb\BreadcrumbFactory;
use App\Domain\SpecDocItem\SpecDocItemFactory;
use App\Domain\SpecDocItem\ValueObject\StatusId;
use App\Domain\SpecDocSheet\SpecDocSheetDto;
use App\Domain\SpecDocSheet\SpecDocSheetFactory;
use App\Domain\SpecDocSheet\ValueObject\StatusId as SpecDocSheetStatusId;
use App\Domain\SpecificationDocument\SpecificationDocumentFactory;
use App\Http\Requests\SpecDocSheetRequest;
use App\UseCases\Breadcrumb\BreadcrumbFindAction;
use App\UseCases\SpecDocItem\SpecDocItemFindAction;
use App\UseCases\SpecDocSheet\SpecDocSheetDeleteAction;
use App\UseCases\SpecDocSheet\SpecDocSheetFindAction;
use App\UseCases\SpecDocSheet\SpecDocSheetStoreAction;
use App\UseCases\SpecificationDocument\SpecificationDocumentFindAction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SpecDocSheetController extends Controller
{
    /**
     * シート一覧(仕様書詳細)画面
     *
     * @return Response
     */
    public function index(
        Request $request,
        SpecificationDocumentFindAction $specificationDocumentFindAction,
        SpecDocSheetFindAction $specDocSheetFindAction,
        BreadcrumbFindAction $breadcrumbFindAction,
    ): Response {
        /** @var int */
        $projectId = $request->input('projectId');
        /** @var int */
        $specDocId = $request->input('specDocId');

        $specDocDto = $specificationDocumentFindAction->findById($specDocId);

        $specDocSheetDtoArr = $specDocSheetFindAction->findAllBySpecDocId($specDocId);
        $specDocSheets      = array_map(function ($dto) {
            return SpecDocSheetFactory::create($dto)->toArray();
        }, $specDocSheetDtoArr);

        $breadcrumbDtoArr = $breadcrumbFindAction->generateBreadcrumbs(projectId: $projectId, specDocId: $specDocId);
        $breadcrumbs      = array_map(function ($dto) {
            return BreadcrumbFactory::create($dto)->toArray();
        }, $breadcrumbDtoArr);

        return Inertia::render('SpecDocSheet/Index', [
            'specDoc'       => SpecificationDocumentFactory::create($specDocDto)->toArray(),
            'specDocSheets' => $specDocSheets,
            'sheetStatuses' => SpecDocSheetStatusId::STATUSES,
            'breadcrumbs'   => $breadcrumbs,
        ]);
    }

    /**
     * テスト実施画面
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\UseCases\SpecificationDocument\SpecificationDocumentFindAction $specificationDocumentFindAction
     * @param \App\UseCases\SpecDocSheet\SpecDocSheetFindAction $specDocSheetFindAction
     * @param \App\UseCases\SpecDocItem\SpecDocItemFindAction $specDocItemFindAction
     * @return \Inertia\Response
     */
    public function show(
        Request $request,
        SpecificationDocumentFindAction $specificationDocumentFindAction,
        SpecDocSheetFindAction $specDocSheetFindAction,
        SpecDocItemFindAction $specDocItemFindAction,
        BreadcrumbFindAction $breadcrumbFindAction,
    ): Response {
        /** @var int */
        $projectId = $request->input('projectId');
        /** @var int */
        $specDocId = $request->input('specDocId');
        /** @var int */
        $specDocSheetId = $request->input('specDocSheetId');

        $specDocDto = $specificationDocumentFindAction->findById($specDocId);

        $specDocSheetDto = $specDocSheetFindAction->findById($specDocSheetId);

        $specDocItemDtoArr = $specDocItemFindAction->findAllBySpecDocSheetId($specDocSheetId);
        $specDocItems      = array_map(function ($dto) {
            return SpecDocItemFactory::create($dto)->toArray();
        }, $specDocItemDtoArr);

        $breadcrumbDtoArr = $breadcrumbFindAction->generateBreadcrumbs(
            projectId: $projectId,
            specDocId: $specDocId,
            specDocSheetId: $specDocSheetId,
        );
        $breadcrumbs = array_map(function ($dto) {
            return BreadcrumbFactory::create($dto)->toArray();
        }, $breadcrumbDtoArr);

        return Inertia::render('SpecDocSheet/Show', [
            'specDoc'      => SpecificationDocumentFactory::create($specDocDto)->toArray(),
            'specDocSheet' => SpecDocSheetFactory::create($specDocSheetDto)->toArray(),
            'specDocItems' => $specDocItems,
            'statuses'     => StatusId::STATUSES,
            'breadcrumbs'  => $breadcrumbs,
        ]);
    }

    public function store(
        SpecDocSheetRequest $request,
        SpecDocSheetStoreAction $specDocSheetStoreAction,
    ): JsonResponse {
        /** @var int */
        $specDocId = $request->input('specDocId');
        /** @var int */
        $execEnvId = $request->validated('exec_env_id');

        $dto = new SpecDocSheetDto(
            id: null,
            specDocId: $specDocId,
            execEnvId: $execEnvId,
            statusId: 0,
            updatedAt: 'now',
        );
        try {
            $newSheetId = $specDocSheetStoreAction->store($dto);
        } catch (Exception $e) {
            Log::error('Failed to create spec doc:' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());

            return response()->json(
                [
                    'message' => 'Failed to create spec doc sheet.',
                ],
                500,
            );
        }

        return response()->json([
            'message'           => 'Success create spec doc sheet.',
            'newSpecDocSheetId' => $newSheetId,
        ], 200);
    }

    /**
     * シート編集画面
     *
     * @param Request $request
     * @param SpecificationDocumentFindAction $specificationDocumentFindAction
     * @param SpecDocSheetFindAction $specDocSheetFindAction
     * @return Response
     */
    public function edit(
        Request $request,
        SpecificationDocumentFindAction $specificationDocumentFindAction,
        SpecDocSheetFindAction $specDocSheetFindAction,
        SpecDocItemFindAction $specDocItemFindAction,
    ): Response {
        /** @var int */
        $specDocId = $request->input('specDocId');
        /** @var int */
        $specDocSheetId = $request->input('specDocSheetId');

        $specDocDto        = $specificationDocumentFindAction->findById($specDocId);
        $specDocSheetDto   = $specDocSheetFindAction->findById($specDocSheetId);
        $specDocItemDtoArr = $specDocItemFindAction->findAllBySpecDocSheetId($specDocSheetId);

        $specDocItems = array_map(function ($dto) {
            return SpecDocItemFactory::create($dto)->toArray();
        }, $specDocItemDtoArr);

        return Inertia::render('SpecDocSheet/Edit', [
            'specDoc'      => SpecificationDocumentFactory::create($specDocDto)->toArray(),
            'specDocSheet' => SpecDocSheetFactory::create($specDocSheetDto)->toArray(),
            'specDocItems' => $specDocItems,
        ]);
    }

    /**
     * シート削除処理
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\UseCases\SpecDocSheet\SpecDocSheetDeleteAction $specDocSheetDeleteAction
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(
        Request $request,
        SpecDocSheetDeleteAction $specDocSheetDeleteAction,
    ): JsonResponse {
        /** @var int */
        $specDocSheetId = $request->input('specDocSheetId');

        try {
            $specDocSheetDeleteAction->deleteById($specDocSheetId);
        } catch (Exception $e) {
            Log::error('Failed to delete spec doc:' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());

            return response()->json([
                'message' => 'Failed to delete spec soc sheet.',
            ], 500);
        }

        return response()->json([
            'message' => 'Success delete spec doc sheet.',
        ], 200);
    }

    /**
     * プレビュー画面
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\UseCases\SpecificationDocument\SpecificationDocumentFindAction $specificationDocumentFindAction
     * @param \App\UseCases\SpecDocSheet\SpecDocSheetFindAction $specDocSheetFindAction
     * @param \App\UseCases\SpecDocItem\SpecDocItemFindAction $specDocItemFindAction
     * @return \Inertia\Response
     */
    public function preview(
        Request $request,
        SpecificationDocumentFindAction $specificationDocumentFindAction,
        SpecDocSheetFindAction $specDocSheetFindAction,
        SpecDocItemFindAction $specDocItemFindAction,
    ): Response {
        /** @var int */
        $specDocId = $request->input('specDocId');
        /** @var int */
        $specDocSheetId = $request->input('specDocSheetId');

        $specDocDto = $specificationDocumentFindAction->findById($specDocId);

        $specDocSheetDto = $specDocSheetFindAction->findById($specDocSheetId);

        $specDocItemDtoArr = $specDocItemFindAction->findAllBySpecDocSheetId($specDocSheetId);
        $specDocItems      = array_map(function ($dto) {
            return SpecDocItemFactory::create($dto)->toArray();
        }, $specDocItemDtoArr);

        return Inertia::render('SpecDocSheet/Show', [
            'specDoc'      => SpecificationDocumentFactory::create($specDocDto)->toArray(),
            'specDocSheet' => SpecDocSheetFactory::create($specDocSheetDto)->toArray(),
            'specDocItems' => $specDocItems,
            'statuses'     => StatusId::STATUSES,
        ]);
    }
}
