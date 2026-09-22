<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Support;

use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Finance\Bic;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Format\Base64String;
use MSSTC4PHP\ValueObject\Format\ClassString;
use MSSTC4PHP\ValueObject\Format\HexColor;
use MSSTC4PHP\ValueObject\Format\JsonString;
use MSSTC4PHP\ValueObject\Format\PhoneNumber;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Format\Slug;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\I18n\Locale;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\DomainName;
use MSSTC4PHP\ValueObject\Network\Hostname;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Network\Uri;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Network\UrlPath;
use MSSTC4PHP\ValueObject\Network\UrlScheme;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use MSSTC4PHP\ValueObject\Temporal\Duration;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;

/**
 * One deliberately awkward value per registered type.
 *
 * Both defects found so far -- BrickMoneyType writing what it could not read,
 * DateRangeType dropping microseconds and the zone -- survived the existing
 * round-trip tests because those use one canonical sample per type. These
 * samples push on the serialisation instead: maximal fractions, microseconds
 * and a DST fall-back, non-default bounds, upper-case and alternative
 * separators, full URIs, extreme integers.
 */
final class NonCanonicalSamples
{
    private function __construct()
    {
    }

    /**
     * @return array<string, ValueObject|Money>
     */
    public static function all(): array
    {
        $ny = new DateTimeZone('America/New_York');

        return [
            'vo_base64_string' => Base64String::from(\base64_encode("line\n\x00binary")),
            'vo_bic' => Bic::from('DEUTDEFF500'),
            'vo_bounded_string' => new BoundedString('abc', 1, 10),
            'vo_cidr' => Cidr::from('2001:db8::/32'),
            'vo_class_string' => ClassString::from(DateRange::class),
            'vo_credit_card_number' => CreditCardNumber::from('378282246310005'),
            // The second of the two identical 01:30 instants of the US fall-back,
            // carrying microseconds -- the exact shape that used to come back wrong.
            'vo_date_range' => new DateRange(
                new DateTimeImmutable('2026-11-01 06:30:00.123456', new DateTimeZone('UTC'))->setTimezone($ny),
                new DateTimeImmutable('2026-11-02 06:30:00.654321', new DateTimeZone('UTC'))->setTimezone($ny),
            ),
            'vo_decimal' => Decimal::from('-0.000000000000001'),
            'vo_domain_name' => DomainName::from('sub.example.co.uk'),
            'vo_duration' => Duration::from('P1Y2M3DT4H5M6S'),
            'vo_email_address' => EmailAddress::from('a+tag@sub.example.co.uk'),
            'vo_geo_coordinates' => new GeoCoordinates(Latitude::from(-89.999999), Longitude::from(179.999999)),
            'vo_hex_color' => HexColor::from('#abc'),
            'vo_hostname' => Hostname::from('sub.example.co.uk'),
            'vo_iban' => Iban::from('MT84MALT011000012345MTLCAST001S'),
            'vo_int_range' => new IntRange(-5, 5),
            'vo_ip_address' => IpAddress::fromString('2001:db8:85a3::8a2e:370:7334'),
            'vo_ipv4_address' => Ipv4Address::from('255.255.255.255'),
            'vo_ipv6_address' => Ipv6Address::from('2001:db8:85a3::8a2e:370:7334'),
            'vo_json_string' => JsonString::from('{"a":[1,2,{"б":"ю"}]}'),
            'vo_locale' => Locale::from('pt-BR'),
            'vo_mac_address' => MacAddress::from('AA-BB-CC-DD-EE-FF'),
            'vo_money' => Money::of('1.2345', 'USD', new CustomContext(4)),
            'vo_non_empty_string' => NonEmptyString::from(' мир 😀 '),
            'vo_non_negative_int' => NonNegativeInt::from(0),
            'vo_percentage' => Percentage::from(33.333333),
            'vo_phone_number' => PhoneNumber::from('+442071838750'),
            'vo_port' => Port::from(65535),
            'vo_positive_int' => PositiveInt::from(\PHP_INT_MAX),
            'vo_semver' => SemVer::from('1.0.0-alpha.1+build.99'),
            'vo_slug' => Slug::from('a-very-long-slug-2026-with-numbers-42'),
            'vo_time_of_day' => TimeOfDay::from('23:59:59'),
            'vo_uri' => Uri::from('https://u:p@example.com:8443/a/b?x=1&y=2#frag'),
            'vo_url' => Url::from('https://example.com:8443/a?b=1#c'),
            'vo_url_path' => UrlPath::from('/a%20b/c'),
            'vo_url_scheme' => UrlScheme::from('ftp'),
            'vo_uuid' => Uuid::from('018F8C1E-7B2A-7CDE-8F01-2A3B4C5D6E7F'),
        ];
    }
}
