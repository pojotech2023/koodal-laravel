<?php

namespace App\Services\Matching;

use App\Services\Matching\Criteria\AgeCriterion;
use App\Services\Matching\Criteria\CommunityCriterion;
use App\Services\Matching\Criteria\EducationCriterion;
use App\Services\Matching\Criteria\HeightCriterion;
use App\Services\Matching\Criteria\IncomeCriterion;
use App\Services\Matching\Criteria\LifestyleCriterion;
use App\Services\Matching\Criteria\LocationCriterion;
use App\Services\Matching\Criteria\MotherTongueCriterion;
use App\Services\Matching\Criteria\OccupationCriterion;
use App\Services\Matching\Criteria\ReligionCriterion;
use Illuminate\Contracts\Container\Container;

/**
 * Maps the weight keys in config/matrimony.php to the classes that implement
 * them.
 *
 * The map is explicit rather than discovered by scanning the directory: a
 * criterion with no weight must not silently start scoring because someone
 * dropped a file in, and a weight with no class must fail loudly at boot
 * rather than quietly contributing zero to every score.
 */
class CriterionRegistry
{
    /** @var array<string, class-string<Criterion>> */
    public const MAP = [
        'religion' => ReligionCriterion::class,
        'community' => CommunityCriterion::class,
        'age' => AgeCriterion::class,
        'mother_tongue' => MotherTongueCriterion::class,
        'location' => LocationCriterion::class,
        'education' => EducationCriterion::class,
        'occupation' => OccupationCriterion::class,
        'lifestyle' => LifestyleCriterion::class,
        'income' => IncomeCriterion::class,
        'height' => HeightCriterion::class,
    ];

    public function __construct(protected Container $container) {}

    /** @return array<string, Criterion> */
    public function all(): array
    {
        $criteria = [];

        foreach (array_keys(config('matrimony.matching.weights', [])) as $key) {
            if (! isset(static::MAP[$key])) {
                throw new \LogicException(
                    "matrimony.matching.weights.{$key} has no Criterion class. ".
                    'Add it to CriterionRegistry::MAP or remove the weight.'
                );
            }

            $criteria[$key] = $this->container->make(static::MAP[$key]);
        }

        return $criteria;
    }
}
