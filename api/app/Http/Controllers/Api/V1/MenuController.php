<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderMenusRequest;
use App\Http\Requests\Api\V1\StoreMenuRequest;
use App\Http\Requests\Api\V1\UpdateMenuRequest;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    /**
     * List all menu entries
     *
     * The whole tree, unfiltered, flattened depth-first (`depth` 0 = top level) in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Menu::class);

        $byParent = Menu::query()->orderBy('sort')->orderBy('id')->get()->groupBy(fn (Menu $m) => $m->parent_id ?? 0);
        $rows = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$rows, $byParent): void {
            foreach ($byParent->get($parent, collect()) as $menu) {
                $menu->setAttribute('depth', $depth);
                $rows[] = $menu;
                $walk($menu->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return MenuResource::collection(collect($rows));
    }

    /** Add a custom menu entry (a group, or a link guarded by a permission) */
    public function store(StoreMenuRequest $request): JsonResponse
    {
        $menu = Menu::query()->create([
            ...$request->validated(),
            'custom' => true,
            'sort' => ((int) Menu::query()->where('parent_id', $request->validated('parent_id'))->max('sort')) + 10,
        ]);

        return (new MenuResource($menu->refresh()))->response()->setStatusCode(201);
    }

    /** Delete a custom menu entry; seeded entries and groups that still have children are refused */
    public function destroy(Menu $menu): Response
    {
        Gate::authorize('delete', $menu);

        if (! $menu->custom) {
            throw ValidationException::withMessages(['menu' => 'Seeded menu entries cannot be deleted; hide them instead.']);
        }

        if ($menu->children()->exists()) {
            throw ValidationException::withMessages(['menu' => 'Delete or move the entries inside this group first.']);
        }

        $menu->delete();

        return response()->noContent();
    }

    /** Update a menu entry's title or icon */
    public function update(UpdateMenuRequest $request, Menu $menu): MenuResource
    {
        $menu->update($request->validated());

        return new MenuResource($menu);
    }

    /**
     * Reorder menu entries
     *
     * `ids` is the new order of one set of siblings; entries keep their parent. Ids from different parents are rejected.
     */
    public function reorder(ReorderMenusRequest $request): JsonResponse
    {
        $ids = $request->ids();

        if (Menu::query()->whereIn('id', $ids)->pluck('parent_id')->unique()->count() > 1) {
            throw ValidationException::withMessages(['ids' => 'Menu entries must share one parent.']);
        }

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $i => $id) {
                Menu::query()->whereKey($id)->update(['sort' => ($i + 1) * 10]);
            }
        });

        return response()->json(['data' => ['reordered' => count($ids)]]);
    }
}
