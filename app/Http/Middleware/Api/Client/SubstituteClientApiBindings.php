<?php

namespace Kriegerhost\Http\Middleware\Api\Client;

use Closure;
use Kriegerhost\Models\User;
use Kriegerhost\Models\Backup;
use Kriegerhost\Models\Database;
use Illuminate\Container\Container;
use Kriegerhost\Contracts\Extensions\HashidsInterface;
use Kriegerhost\Http\Middleware\Api\ApiSubstituteBindings;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;

class SubstituteClientApiBindings extends ApiSubstituteBindings
{
    /**
     * Perform substitution of route parameters without triggering
     * a 404 error if a model is not found.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->bind('server', function ($value) use ($request) {
            try {
                $column = 'uuidShort';
                if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $value)) {
                    $column = 'uuid';
                }

                return Container::getInstance()->make(ServerRepositoryInterface::class)->findFirstWhere([
                    [$column, '=', $value],
                ]);
            } catch (RecordNotFoundException $ex) {
                $request->attributes->set('is_missing_model', true);

                return null;
            }
        });

        $this->router->bind('database', function ($value) {
            $id = Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);

            return Database::query()->where('id', $id)->firstOrFail();
        });

        $this->router->bind('backup', function ($value) {
            return Backup::query()->where('uuid', $value)->firstOrFail();
        });

        $this->router->bind('user', function ($value) {
            return User::query()->where('uuid', $value)->firstOrFail();
        });

        return parent::handle($request, $next);
    }
}
