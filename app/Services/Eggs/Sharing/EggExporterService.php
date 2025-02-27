<?php

namespace Kriegerhost\Services\Eggs\Sharing;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kriegerhost\Models\EggVariable;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;

class EggExporterService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggExporterService constructor.
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Return a JSON representation of an egg and its variables.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $egg): string
    {
        $egg = $this->repository->getWithExportAttributes($egg);

        $struct = [
            '_comment' => 'DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PTERODACTYL PANEL - PTERODACTYL.IO',
            'meta' => [
                'version' => 'PTDL_v1',
                'update_url' => $egg->update_url,
            ],
            'exported_at' => Carbon::now()->toIso8601String(),
            'name' => $egg->name,
            'author' => $egg->author,
            'description' => $egg->description,
            'features' => $egg->features,
            'images' => $egg->docker_images,
            'file_denylist' => Collection::make($egg->inherit_file_denylist)->filter(function ($value) {
                return !empty($value);
            }),
            'startup' => $egg->startup,
            'config' => [
                'files' => $egg->inherit_config_files,
                'startup' => $egg->inherit_config_startup,
                'logs' => $egg->inherit_config_logs,
                'stop' => $egg->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $egg->copy_script_install,
                    'container' => $egg->copy_script_container,
                    'entrypoint' => $egg->copy_script_entry,
                ],
            ],
            'variables' => $egg->variables->transform(function (EggVariable $item) {
                return Collection::make($item->toArray())
                    ->except(['id', 'egg_id', 'created_at', 'updated_at'])
                    ->toArray();
            }),
        ];

        return json_encode($struct, JSON_PRETTY_PRINT);
    }
}
