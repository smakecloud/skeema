<?php

namespace Tests\Commands;

use Smakecloud\Skeema\Commands\SkeemaDiffCommand;
use Tests\TestCase;

class SkeemaDiffCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:diff')
            ->expectsOutputToContain('Skeema config file not found at')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException(''))->getExitCode()
            );
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        sleep(1);

        $this->artisan('skeema:diff')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_changes_were_found()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-new-column-sql')
        );

        $this->artisan('skeema:diff')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException())->getExitCode()
            );
    }

    /** @test */
    public function is_exits_with_success_if_changes_were_found_but_the_ignore_warnings_flag_is_set()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-new-column-sql')
        );

        $this->artisan('skeema:diff', ['--ignore-warnings' => true])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_errors_were_found()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        $this->artisan('skeema:diff')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException())->getExitCode()
            );
    }

    /** @test */
    public function it_uses_the_right_args()
    {
        $this->app->get('config')->set('skeema.alter_wrapper.enabled', true);
        $this->app->get('config')->set('skeema.alter_wrapper.min_size', '42m');
        $this->app->get('config')->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\AutoIncRule::class => 'lr1',
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'lr2',
            \Smakecloud\Skeema\Lint\CompressionRule::class => 'lr3',
        ]);
        $this->app->get('config')->set('skeema.lint.diff', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'override',
            \Smakecloud\Skeema\Lint\DefinerRule::class => 'added',
        ]);

        $args = $this->getSkeemaArgs(
            SkeemaDiffCommand::class,
            '--alter-algorithm="test1"'
            .' --alter-lock="test2"'
            .' --alter-validate-virtual'
            .' --compare-metadata'
            .' --exact-match'
            .' --allow-unsafe'
            .' --skip-verify'
            .' --partitioning="test3"'
            .' --strip-definer="test4"'
            .' --allow-auto-inc="test5"'
            .' --allow-charset="test6"'
            .' --allow-compression="test7"'
            .' --allow-definer="test8"'
            .' --allow-engine="test9"'
            .' --safe-below-size="test10"'
        );

        $this->assertArrayHasKey('alter-algorithm', $args);
        $this->assertEquals('test1', $args['alter-algorithm']);

        $this->assertArrayHasKey('alter-lock', $args);
        $this->assertEquals('test2', $args['alter-lock']);

        $this->assertArrayHasKey('alter-validate-virtual', $args);
        $this->assertEquals(true, $args['alter-validate-virtual']);

        $this->assertArrayHasKey('compare-metadata', $args);
        $this->assertEquals(true, $args['compare-metadata']);

        $this->assertArrayHasKey('exact-match', $args);
        $this->assertEquals(true, $args['exact-match']);

        $this->assertArrayHasKey('allow-unsafe', $args);
        $this->assertEquals(true, $args['allow-unsafe']);

        $this->assertArrayHasKey('skip-verify', $args);
        $this->assertEquals(true, $args['skip-verify']);

        $this->assertArrayHasKey('partitioning', $args);
        $this->assertEquals('test3', $args['partitioning']);

        $this->assertArrayHasKey('strip-definer', $args);
        $this->assertEquals('test4', $args['strip-definer']);

        $this->assertArrayHasKey('allow-auto-inc', $args);
        $this->assertEquals('test5', $args['allow-auto-inc']);

        $this->assertArrayHasKey('allow-charset', $args);
        $this->assertEquals('test6', $args['allow-charset']);

        $this->assertArrayHasKey('allow-compression', $args);
        $this->assertEquals('test7', $args['allow-compression']);

        $this->assertArrayHasKey('allow-definer', $args);
        $this->assertEquals('test8', $args['allow-definer']);

        $this->assertArrayHasKey('allow-engine', $args);
        $this->assertEquals('test9', $args['allow-engine']);

        $this->assertArrayHasKey('safe-below-size', $args);
        $this->assertEquals('test10', $args['safe-below-size']);

        $this->assertArrayHasKey('lint-auto-inc', $args);
        $this->assertEquals('lr1', $args['lint-auto-inc']);

        $this->assertArrayHasKey('lint-charset', $args);
        $this->assertEquals('override', $args['lint-charset']);

        $this->assertArrayHasKey('lint-compression', $args);
        $this->assertEquals('lr3', $args['lint-compression']);

        $this->assertArrayHasKey('lint-definer', $args);
        $this->assertEquals('added', $args['lint-definer']);

        $this->assertArrayHasKey('alter-wrapper-min-size', $args);
        $this->assertEquals('42m', $args['alter-wrapper-min-size']);
    }

    /** @test */
    public function it_skips_linting_when_flag_is_set()
    {
        $this->app->get('config')->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\AutoIncRule::class => 'lr1',
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'lr2',
            \Smakecloud\Skeema\Lint\CompressionRule::class => 'lr3',
        ]);
        $this->app->get('config')->set('skeema.lint.diff', false);

        $args = $this->getSkeemaArgs(SkeemaDiffCommand::class, '');

        $this->assertArrayHasKey('skip-lint', $args);
        $this->assertEquals(true, $args['skip-lint']);

        $this->assertArrayNotHasKey('lint-auto-inc', $args);
        $this->assertArrayNotHasKey('lint-charset', $args);
        $this->assertArrayNotHasKey('lint-compression', $args);
    }
}
