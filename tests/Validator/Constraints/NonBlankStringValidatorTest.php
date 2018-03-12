<?php

namespace Dashtainer\Tests\Validator\Constraints;

use Dashtainer\Validator\Constraints\NonBlankString;
use Dashtainer\Validator\Constraints\NonBlankStringValidator;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NonBlankStringValidatorTest extends KernelTestCase
{
    /** @var MockObject|NonBlankString */
    protected $constraint;

    /** @var MockObject|ConstraintViolationBuilderInterface */
    protected $constraintBuilder;

    /** @var MockObject|ExecutionContextInterface */
    protected $context;

    /** @var NonBlankStringValidator */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->getMock();

        $this->constraint = $this->getMockBuilder(NonBlankString::class)
            ->setConstructorArgs([])
            ->getMock();

        $this->constraintBuilder = $this->getMockBuilder(
                ConstraintViolationBuilderInterface::class
            )
            ->setConstructorArgs([])
            ->getMock();

        $this->validator = new NonBlankStringValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @var mixed $value
     * @dataProvider getValidateFailsTrim
     */
    public function testValidateFailsTrim($value)
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->constraintBuilder);

        $this->constraintBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ string }}', $value)
            ->willReturnSelf();

        $this->constraintBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($value, $this->constraint);
    }

    public function getValidateFailsTrim()
    {
        yield [''];
        yield ['  '];
        yield ['      '];
        yield [null];
        yield [false];
    }

    /**
     * @var string $value
     * @dataProvider getValidatePasses
     */
    public function testValidatePasses(string $value)
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $this->constraint);
    }

    public function getValidatePasses()
    {
        yield ['a'];
        yield [' a '];
        yield ['   ab   '];
        yield ['                      a'];
        yield ['a                      '];
    }
}
