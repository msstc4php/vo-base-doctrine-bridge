<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Types\Type;

/**
 * Registers every bundled value-object Doctrine type. Call once at bootstrap
 * for bare Doctrine; under Symfony prefer mapping these under `doctrine.dbal.types`.
 * Registration is idempotent so it is safe in long-running workers.
 */
final class TypeRegistrar
{
    /**
     * @var array<string, class-string<Type>>
     */
    private const array TYPES = [
        EmailAddressType::NAME => EmailAddressType::class,
        UuidType::NAME => UuidType::class,
        IpAddressType::NAME => IpAddressType::class,
        Ipv4AddressType::NAME => Ipv4AddressType::class,
        Ipv6AddressType::NAME => Ipv6AddressType::class,
        MacAddressType::NAME => MacAddressType::class,
        HostnameType::NAME => HostnameType::class,
        DomainNameType::NAME => DomainNameType::class,
        UrlType::NAME => UrlType::class,
        UriType::NAME => UriType::class,
        UrlSchemeType::NAME => UrlSchemeType::class,
        UrlPathType::NAME => UrlPathType::class,
        SlugType::NAME => SlugType::class,
        PhoneNumberType::NAME => PhoneNumberType::class,
        SemVerType::NAME => SemVerType::class,
        HexColorType::NAME => HexColorType::class,
        ClassStringType::NAME => ClassStringType::class,
        LocaleType::NAME => LocaleType::class,
        PortType::NAME => PortType::class,
        GeoCoordinatesType::NAME => GeoCoordinatesType::class,
        IbanType::NAME => IbanType::class,
        BicType::NAME => BicType::class,
        CreditCardNumberType::NAME => CreditCardNumberType::class,
        DecimalType::NAME => DecimalType::class,
        CidrType::NAME => CidrType::class,
        TimeOfDayType::NAME => TimeOfDayType::class,
        DurationType::NAME => DurationType::class,
        Base64StringType::NAME => Base64StringType::class,
        JsonStringType::NAME => JsonStringType::class,
        DateRangeType::NAME => DateRangeType::class,
        BrickMoneyType::NAME => BrickMoneyType::class,
    ];

    public static function registerAll(): void
    {
        foreach (self::TYPES as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
    }

    /**
     * @return array<string, class-string<Type>>
     */
    public static function types(): array
    {
        return self::TYPES;
    }
}
