<?php

use Rushing\Graphine\Testing\SeamGuard;

it('passes a clean driver that imports only the MIT Bolt client', function () {
    $offenders = (new SeamGuard)->scan(__DIR__.'/../Fixtures/guard/clean');

    expect($offenders)->toBe([]);
});

it('fails loudly on a driver that leaks a Neo4j server-internal import in-process', function () {
    $offenders = (new SeamGuard)->scan(__DIR__.'/../Fixtures/guard/leaky');

    expect($offenders)->not->toBe([])
        ->and(implode("\n", $offenders))->toContain('Neo4j\Server\Bootstrap');
});

it('passes the package reference driver (it links no boundary engine)', function () {
    $offenders = (new SeamGuard)->scan(dirname(__DIR__, 2).'/src/Drivers');

    expect($offenders)->toBe([]);
});

it('detects a group-use leak, not just a plain use', function () {
    $tmp = sys_get_temp_dir().'/graphine_seamguard_groupuse.php';
    file_put_contents($tmp, <<<'PHP'
        <?php
        namespace App\Graph\Drivers;
        use Pellet\{Reasoner, Config};
        class Leaky {}
        PHP);

    try {
        $offenders = (new SeamGuard)->scan($tmp);
        expect(implode("\n", $offenders))->toContain('Pellet\Reasoner');
    } finally {
        @unlink($tmp);
    }
});

// The inline fixture is written at runtime rather than committed under Fixtures/: pint's
// `fully_qualified_strict_types` lifts a `\Neo4j\Server\Bootstrap::class` into a `use` import,
// which is exactly the distinction these two tests exist to draw.
function inlineReferenceFixture(): string
{
    $tmp = sys_get_temp_dir().'/graphine_seamguard_inline_'.getmypid().'.php';
    file_put_contents($tmp, <<<'PHP'
        <?php
        namespace App\Graph\Drivers;
        class InlineOnly
        {
            public function boot(): void
            {
                $_ = \Neo4j\Server\Bootstrap::class;
            }
        }
        PHP);

    return $tmp;
}

it('counts an inline fully-qualified reference as a leak by default', function () {
    $tmp = inlineReferenceFixture();

    try {
        expect(implode("\n", (new SeamGuard)->scan($tmp)))->toContain('Neo4j\Server\Bootstrap');
    } finally {
        @unlink($tmp);
    }
});

it('ignores an inline fully-qualified reference in imports-only mode, and still catches a use import', function () {
    $tmp = inlineReferenceFixture();
    $guard = new SeamGuard(importsOnly: true);

    try {
        expect($guard->scan($tmp))->toBe([])
            ->and(implode("\n", $guard->scan(__DIR__.'/../Fixtures/guard/leaky')))->toContain('Neo4j\Server\Bootstrap');
    } finally {
        @unlink($tmp);
    }
});
