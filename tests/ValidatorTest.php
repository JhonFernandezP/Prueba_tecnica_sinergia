<?php
declare(strict_types=1);

namespace Tests;

use App\Utils\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidEmailPasses(): void
    {
        $data = ['correo' => 'juan.perez@example.com'];
        $rules = ['correo' => 'required|email'];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
    }

    public function testInvalidEmailFails(): void
    {
        $data = ['correo' => 'correo_no_valido@@algo..com'];
        $rules = ['correo' => 'required|email'];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('correo', $validator->getErrors());
    }

    public function testRequiredFieldsFailWhenEmpty(): void
    {
        $data = ['nombre1' => '', 'apellido1' => ''];
        $rules = [
            'nombre1' => 'required',
            'apellido1' => 'required'
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertCount(2, $validator->getErrors());
    }

    public function testMinAndMaxValidation(): void
    {
        $data = [
            'corto' => 'ab',
            'largo' => 'este_texto_es_demasiado_largo_para_el_limite'
        ];

        $rules = [
            'corto' => 'min:3',
            'largo' => 'max:10'
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('corto', $validator->getErrors());
        $this->assertArrayHasKey('largo', $validator->getErrors());
    }

    public function testSanitizationStripsTagsAndControlChars(): void
    {
        $data = [
            'nombre' => '  <script>alert("hack")</script>Carlos  '
        ];

        $validator = new Validator($data, []);
        $clean = $validator->getSanitizedData();

        $this->assertEquals('alert("hack")Carlos', $clean['nombre']);
    }
}
