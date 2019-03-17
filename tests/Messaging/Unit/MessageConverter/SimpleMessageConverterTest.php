<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\MessageConverter;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\SimpleMessageConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class SimpleMessageConverterText
 * @package Test\SimplyCodedSoftware\Messaging\Unit\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageConverterTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_converting_with_string_payload_and_no_content_type_defined()
    {
        $simpleMessageConverter = new SimpleMessageConverter(AutoCollectionConversionService::createWith([]));

        $payload = "some";

        $this->assertEquals(
            MessageBuilder::withPayload($payload)
                ->setContentType(MediaType::createTextPlain()),
            $simpleMessageConverter->toMessage(
                $payload,
                []
            )
        );

        $this->assertEquals(
            $payload,
            $simpleMessageConverter->fromMessage(
                MessageBuilder::withPayload($payload)->build(),
                TypeDescriptor::createStringType()
            )
        );
    }
}