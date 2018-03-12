<?php

namespace Dashtainer\Tests\Validator\Constraints;

use Dashtainer\Validator\Constraints\Hostname;
use Dashtainer\Validator\Constraints\HostnameValidator;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class HostnameValidatorTest extends KernelTestCase
{
    /** @var MockObject|Hostname */
    protected $constraint;

    /** @var MockObject|ConstraintViolationBuilderInterface */
    protected $constraintBuilder;

    /** @var MockObject|ExecutionContextInterface */
    protected $context;

    /** @var HostnameValidator */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->getMock();

        $this->constraint = $this->getMockBuilder(Hostname::class)
            ->setConstructorArgs([])
            ->getMock();

        $this->constraintBuilder = $this->getMockBuilder(
                ConstraintViolationBuilderInterface::class
            )
            ->setConstructorArgs([])
            ->getMock();

        $this->validator = new HostnameValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @var string $value
     * @dataProvider getValidateFailsFilterVar
     */
    public function testValidateFailsFilterVar(string $value)
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

    public function getValidateFailsFilterVar()
    {
        yield ['foo..'];
        yield ['=foo.com'];
        yield ['foo.com='];
        yield ['foo..com'];
        yield [''];
    }

    /**
     * @var string $value
     * @dataProvider getValidateFailsStrlen
     */
    public function testValidateFailsStrlen(string $value)
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

    public function getValidateFailsStrlen()
    {
        yield ['f'];
        yield ['foooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo.com'];
    }

    /**
     * @var string $value
     * @dataProvider getValidatePassesHostname
     */
    public function testValidatePassesHostname(string $value)
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $this->constraint);
    }

    public function getValidatePassesHostname()
    {
        yield ['f.com'];
        yield ['foooooo.com'];
        yield ['foooooo-com'];
        yield ['f-c'];
        yield ['f-8-c'];
        yield ['f-8-c.com'];
    }
}
