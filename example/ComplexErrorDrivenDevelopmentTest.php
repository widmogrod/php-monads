<?php

declare(strict_types=1);
require_once 'vendor/autoload.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Widmogrod\Functional as f;
use Widmogrod\Monad\Either as E;

function validateName(array $request)
{
    return $request['name'] === ''
        ? E\Left::of('Request name is empty')
        : E\Right::of($request);
}

function validateEmail(array $request)
{
    return $request['email'] === ''
        ? E\Left::of('Request e-mail is empty')
        : E\Right::of($request);
}

function validateNameLength(array $request)
{
    return strlen($request['name']) > 30
        ? E\Left::of('Request name is to long')
        : E\Right::of($request);
}

function validateInput(array $request)
{
    return E\Right::of($request)
        ->bind('validateName')
        ->bind('validateEmail')
        ->bind('validateNameLength');
}

function canonizeEmail(array $request)
{
    $request['email'] = strtolower(trim($request['email']));

    return $request;
}

function updateDatabase(array $request)
{
    // may throw exception
}

function updateDatabaseStep(array $request)
{
    return E\tryCatch(
        f\tee('updateDatabase'),
        function (Exception $e) {
            return $e->getMessage();
        }
    )($request);
}

// sendMessage :: Either a b -> Either c d
function sendMessage(E\Either $either)
{
    return E\doubleMap('returnFailure', 'returnMessage', $either);
}

function returnMessage(array $request)
{
    return [
        'status' => 200,
    ];
}

function returnFailure($data)
{
    return [
        'error' => (string) $data,
    ];
}

function handleRequest(array $request)
{
    return f\pipeline(
        'validateInput',
        f\map('canonizeEmail'),
        f\bind('updateDatabaseStep'),
        'sendMessage'
    )($request);
}

class ComplexErrorDrivenDevelopmentTest extends TestCase
{
    #[DataProvider('provideData')]
    public function test_it_should_be_prepared_of_errors(array $request, $isError, $expected)
    {
        $result = handleRequest($request);

        $this->assertInstanceOf(
            $isError ? E\Left::class : E\Right::class,
            $result
        );

        $this->assertEquals($expected, f\valueOf($result));
    }

    public static function provideData()
    {
        return [
            'success case' => [
                [
                    'name' => 'Jone Doe',
                    'email' => 'test@example.com'
                ],
                false,
                ['status' => 200],
            ],
            'username to short' => [
                [
                    'name' => '',
                    'email' => 'test@example.com'
                ],
                true,
                ['error' => 'Request name is empty'],
            ],
            'username to long' => [
                [
                    'name' => 'asd asdasdlaks askl djalskd jalskdjaslkdjasldjadsa asd',
                    'email' => 'test@example.com'
                ],
                true,
                ['error' => 'Request name is to long'],
            ],
            'email empty' => [
                [
                    'name' => 'Jone Doe',
                    'email' => ''
                ],
                true,
                ['error' => 'Request e-mail is empty'],
            ],
        ];
    }
}
