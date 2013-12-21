<?php

require_once 'TestHelper.php';
require_once(APP_DIR . '/OpenmixApplication.php');

class OpenmixApplicationTests extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function init()
    {
        $config = $this->getMock('Configuration');
        $application = new OpenmixApplication();
        $application->cnames = array(
            'a' => 'a.foo.com',
            'b' => 'b.foo.com',
            'c' => 'c.foo.com',
        );
        $application->default_providers = array( 'a', 'b' );
        $call_index = 0;
        
        // Response options
        $config->expects($this->at($call_index++))->method('declareResponseOption')
            ->with('a', 'a.foo.com', 20);
            
        $config->expects($this->at($call_index++))->method('declareResponseOption')
            ->with('b', 'b.foo.com', 20);
        
        $config->expects($this->at($call_index++))->method('declareResponseOption')
            ->with('c', 'c.foo.com', 20);
        
        $config->expects($this->exactly(3))->method('declareResponseOption');
        
        // Reason codes
        $config->expects($this->at($call_index++))->method('declareReasonCode')->with('A');
        //$config->expects($this->at($call_index++))->method('declareReasonCode')->with('B');
        //$config->expects($this->at($call_index++))->method('declareReasonCode')->with('C');
        $config->expects($this->at($call_index++))->method('declareReasonCode')->with('D');
        $config->expects($this->at($call_index++))->method('declareReasonCode')->with('E');
        $config->expects($this->at($call_index++))->method('declareReasonCode')->with('F');
        $config->expects($this->exactly(4))->method('declareReasonCode');

        // Input
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('real:score:avail', 'a,b');
        
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('real:score:http_rtt', 'a,b,c');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('real:plive:live', 'a,b,c');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('string:geo:country_iso');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('integer:geo:asn');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('integer:enable_edns:enable_edns');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('string:edns:country_iso');
            
        $config->expects($this->at($call_index++))
            ->method('declareInput')
            ->with('integer:edns:asn');
        
        $config->expects($this->exactly(8))->method('declareInput');

        // Code under test
        $application->init($config);
    }
    
    /**
     * @test
     */
    public function service()
    {
        $test_data = array(
            array(
                'description' => 'default providers; all available; RTT-based routing; a selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'avail' => array( 'a' => 100.0, 'b' => 100.0 ),
                'sonar' => array( 'a' => 100.0, 'b' => 100.0, 'c' => 100.0 ),
                'rtt' => array( 'a' => 200.1, 'b' => 200.2, 'c' => 200.3 ),
                'alias' => 'a',
                'reason' => 'A',
            ),
            array(
                'description' => 'default providers; all available; RTT-based routing; b selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'avail' => array( 'a' => 100.0, 'b' => 100.0 ),
                'sonar' => array( 'a' => 100.0, 'b' => 100.0, 'c' => 100.0 ),
                'rtt' => array( 'a' => 200.3, 'b' => 200.2, 'c' => 200.3 ),
                'alias' => 'b',
                'reason' => 'A',
            ),
            array(
                'description' => 'default providers; all available; RTT-based routing; ' .
                    'c fastest but b selected because c is not a candidate',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'avail' => array( 'a' => 100.0, 'b' => 100.0 ),
                'sonar' => array( 'a' => 100.0, 'b' => 100.0, 'c' => 100.0 ),
                'rtt' => array( 'a' => 200.5, 'b' => 200.4, 'c' => 200.3 ),
                'alias' => 'b',
                'reason' => 'A',
            ),
            array(
                'description' => 'default providers; only one candidate available (says sonar); a selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'avail' => array( 'a' => 100.0, 'b' => 100.0 ),
                'sonar' => array( 'a' => 100.0, 'b' => 74.99999, 'c' => 100.0 ),
                'alias' => 'a',
                'reason' => 'D',
            ),
            array(
                'description' => 'default providers; only one candidate available (says sonar); b selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'avail' => array( 'a' => 100.0, 'b' => 100.0 ),
                'sonar' => array( 'a' => 74.99999, 'b' => 100.0, 'c' => 100.0 ),
                'alias' => 'b',
                'reason' => 'D',
            ),
            array(
                'description' => 'default providers; only one candidate available (says radar); a selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'avail' => array( 'a' => 90.0, 'b' => 89.99999 ),
                'sonar' => array( 'a' => 100.0, 'b' => 100.0, 'c' => 100.0 ),
                'alias' => 'a',
                'reason' => 'D',
            ),
            array(
                'description' => 'default providers; only one candidate available (says radar); b selected',
                'default_providers' => array( 'a', 'b' ),
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'avail' => array( 'a' => 89.99999, 'b' => 90.0 ),
                'sonar' => array( 'a' => 100.0, 'b' => 100.0, 'c' => 100.0 ),
                'alias' => 'b',
                'reason' => 'D',
            ),
            array(
                'description' => 'default providers; no candidates available (says sonar); a selected',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'avail' => array( 'a' => 90.0, 'b' => 90.0 ),
                'sonar' => array( 'a' => 74.99999, 'b' => 74.99999, 'c' => 100.0 ),
                'alias' => 'blah',
                'reason' => 'E',
            ),
            array(
                'description' => 'default providers; all available; RTT-based routing; no valid RTT',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'avail' => array( 'a' => 90, 'b' => 90 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75 ),
                'rtt' => array( 'a' => 9.99999, 'b' => 9.99999, 'c' => 200 ),
                'alias' => 'blah',
                'reason' => 'F',
            ),
            array(
                'description' => 'ASN override; all available; RTT-based routing; b selected',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'asn_overrides' => array(
                    '12345' => array( 'b', 'c' ),
                ),
                'country_overrides' => array(
                    'whatever' => array( 'a' ),
                ),
                'avail' => array( 'a' => 90, 'b' => 90 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75 ),
                'rtt' => array( 'a' => 200.1, 'b' => 200.2, 'c' => 200.3 ),
                'alias' => 'b',
                'reason' => 'A',
            ),
            array(
                'description' => 'ASN override; all available; RTT-based routing; c selected',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'asn_overrides' => array(
                    '12345' => array( 'b', 'c' ),
                ),
                'country_overrides' => array(
                    'whatever' => array( 'a' ),
                ),
                'avail' => array( 'a' => 90, 'b' => 90 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75 ),
                'rtt' => array( 'a' => 200.1, 'b' => 200.3, 'c' => 200.2 ),
                'alias' => 'c',
                'reason' => 'A',
            ),
            array(
                'description' => 'country override; all available; RTT-based routing; c selected',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'country_overrides' => array(
                    'whatever' => array( 'c', 'd' ),
                ),
                'avail' => array( 'a' => 90, 'b' => 90 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75, 'd' => 75 ),
                'rtt' => array( 'a' => 200.1, 'b' => 200.2, 'c' => 200.3, 'd' => 2004 ),
                'alias' => 'c',
                'reason' => 'A',
            ),
            array(
                'description' => 'country override; all available; RTT-based routing; d selected',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'country_overrides' => array(
                    'whatever' => array( 'c', 'd' ),
                ),
                'avail' => array( 'a' => 90, 'b' => 90 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75, 'd' => 75 ),
                'rtt' => array( 'a' => 200.1, 'b' => 200.2, 'c' => 200.4, 'd' => 200.3 ),
                'alias' => 'd',
                'reason' => 'A',
            ),
            array(
                'description' => 'country override; none available (says radar); RTT-based routing',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'country_overrides' => array(
                    'whatever' => array( 'b', 'c', 'd' ),
                ),
                'avail' => array( 'a' => 0, 'b' => 0 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75, 'd' => 75 ),
                'rtt' => array( 'a' => 200, 'b' => 200, 'c' => 199, 'd' => 200 ),
                'alias' => 'c',
                'reason' => 'A',
            ),
            array(
                'description' => 'country override; none available (says radar); 1 available says sonar; select c',
                'default_providers' => array( 'a', 'b' ),
                'last_resort_provider' => 'blah',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 0,
                'availability_threshold' => 90,
                'sonar_threshold' => 75,
                'min_valid_rtt' => 10,
                'country_overrides' => array(
                    'whatever' => array( 'b', 'c', 'd' ),
                ),
                'avail' => array( 'a' => 0, 'b' => 0 ),
                'sonar' => array( 'a' => 75, 'b' => 75, 'c' => 75, 'd' => 74.99999 ),
                'alias' => 'c',
                'reason' => 'D',
            ),
            array(
                'description' => 'Production scenario; data says CDN.NET is fastest',
                'geo_asn' => 12345,
                'geo_country' => 'whatever',
                'enable_edns' => 1,
                'edns_asn' => 30736,
                'edns_country' => 'DK',
                'avail' => from_context('avail', '12-5-0-0=0;13-6-0-0=0;4-2-0-0=30736;5-0-11358-0=100.000000;5-0-10148-0=100.000000;5-0-7844-0=100.000000;5-0-7845-0=100.000000;5-0-7859-0=100.000000;5-0-8423-0=100.000000;5-0-8506-0=100.000000;5-0-8593-0=100.000000;5-0-10077-0=100.000000;5-0-10052-0=100.000000;1-0-428-2=61.599998;1-0-275-2=69.199997;1-0-7844-2=97.111115;1-0-7845-2=78.166664;1-0-7859-2=62.500000;1-0-8423-2=276.454559;1-0-8506-2=53.666668;1-0-8593-2=67.285713;1-0-10077-2=345.250000;1-0-10052-2=68.833336;1-3-275-2=100.000000;1-3-428-2=100.000000;12-4-0=XX;4-1-0=DK;'),
                'sonar' => from_context('sonar', '12-5-0-0=0;13-6-0-0=0;4-2-0-0=30736;5-0-11358-0=100.000000;5-0-10148-0=100.000000;5-0-7844-0=100.000000;5-0-7845-0=100.000000;5-0-7859-0=100.000000;5-0-8423-0=100.000000;5-0-8506-0=100.000000;5-0-8593-0=100.000000;5-0-10077-0=100.000000;5-0-10052-0=100.000000;1-0-428-2=61.599998;1-0-275-2=69.199997;1-0-7844-2=97.111115;1-0-7845-2=78.166664;1-0-7859-2=62.500000;1-0-8423-2=276.454559;1-0-8506-2=53.666668;1-0-8593-2=67.285713;1-0-10077-2=345.250000;1-0-10052-2=68.833336;1-3-275-2=100.000000;1-3-428-2=100.000000;12-4-0=XX;4-1-0=DK;'),
                'rtt' => from_context('rtt', '12-5-0-0=0;13-6-0-0=0;4-2-0-0=30736;5-0-11358-0=100.000000;5-0-10148-0=100.000000;5-0-7844-0=100.000000;5-0-7845-0=100.000000;5-0-7859-0=100.000000;5-0-8423-0=100.000000;5-0-8506-0=100.000000;5-0-8593-0=100.000000;5-0-10077-0=100.000000;5-0-10052-0=100.000000;1-0-428-2=61.599998;1-0-275-2=69.199997;1-0-7844-2=97.111115;1-0-7845-2=78.166664;1-0-7859-2=62.500000;1-0-8423-2=276.454559;1-0-8506-2=53.666668;1-0-8593-2=67.285713;1-0-10077-2=345.250000;1-0-10052-2=68.833336;1-3-275-2=100.000000;1-3-428-2=100.000000;12-4-0=XX;4-1-0=DK;'),
                'alias' => 'cdn_net',
                'reason' => 'A',
            ),
        );
        
        $test_index = 0;
        foreach ($test_data as $i) {
            if (array_key_exists('description', $i)) {
                print("\nTest " . $test_index++ . ': ' . $i['description']);
            }
            else {
                print("\nTest " . $test_index++);
            }
            $request = $this->getMock('Request');
            $response = $this->getMock('Response');
            $utilities = $this->getMock('Utilities');
            $application = new OpenmixApplication();

            if (array_key_exists('default_providers', $i)) {
                $application->default_providers = $i['default_providers'];
            }

            if (array_key_exists('last_resort_provider', $i)) {
                $application->last_resort_provider = $i['last_resort_provider'];
            }

            if (array_key_exists('asn_overrides', $i)) {
                $application->asn_overrides = $i['asn_overrides'];
            }
            else {
                $application->asn_overrides = array();
            }

            if (array_key_exists('country_overrides', $i)) {
                $application->country_overrides = $i['country_overrides'];
            }
            else {
                $application->country_overrides = array();
            }

            if (array_key_exists('availability_threshold', $i)) {
                $application->availability_threshold = $i['availability_threshold'];
            }

            if (array_key_exists('sonar_threshold', $i)) {
                $application->sonar_threshold = $i['sonar_threshold'];
            }

            if (array_key_exists('min_valid_rtt', $i)) {
                $application->min_valid_rtt = $i['min_valid_rtt'];
            }

            $request_call_index = 0;
            $request->expects($this->at($request_call_index++))
                ->method('geo')
                ->with('integer:geo:asn')
                ->will($this->returnValue($i['geo_asn']));
                
            $request->expects($this->at($request_call_index++))
                ->method('geo')
                ->with('string:geo:country_iso')
                ->will($this->returnValue($i['geo_country']));
                
            $request->expects($this->at($request_call_index++))
                ->method('geo')
                ->with('integer:enable_edns:enable_edns')
                ->will($this->returnValue($i['enable_edns']));
                
            if (array_key_exists('edns_asn', $i)) {
                $request->expects($this->at($request_call_index++))
                    ->method('geo')
                    ->with('integer:edns:asn')
                    ->will($this->returnValue($i['edns_asn']));
            }
            
            if (array_key_exists('edns_country', $i)) {
                $request->expects($this->at($request_call_index++))
                    ->method('geo')
                    ->with('string:edns:country_iso')
                    ->will($this->returnValue($i['edns_country']));
            }
            
            if (array_key_exists('avail', $i)) {
                $request->expects($this->at($request_call_index++))
                    ->method('radar')
                    ->with('real:score:avail')
                    ->will($this->returnValue($i['avail']));
            }
            
            if (array_key_exists('sonar', $i)) {
                $request->expects($this->at($request_call_index++))
                    ->method('pulse')
                    ->with('real:plive:live')
                    ->will($this->returnValue($i['sonar']));
            }
            
            if (array_key_exists('rtt', $i)) {
                $request->expects($this->at($request_call_index++))
                    ->method('radar')
                    ->with('real:score:http_rtt')
                    ->will($this->returnValue($i['rtt']));
            }
            
            $response->expects($this->once())->method('selectProvider')->with($i['alias']);
            $utilities->expects($this->never())->method('selectRandom');

            $response->expects($this->once())
                ->method('setReasonCode')
                ->with($i['reason']);

            // Code under test
            $application->service($request, $response, $utilities);

            // Assert
            $this->verifyMockObjects();
        }
    }
}

function from_context($type, $context) {

    $aliases = array(
        '11358' => 'cdn_net',
        '10148' => 'maxcdn',
        '7844' => 'leap-pt',
        '7845' => 'leap-ua',
        '7859' => 'prome-it',
        '8423' => 'exvm-sg',
        '8506' => 'knight-nl',
        '8593' => 'alpine-ch',
        '10077' => 'jetdi-id',
        '10052' => 'finn-fr',
        '275' => 'maxcdn',
        '428' => 'cdn_net',
    );

    $result = array();
    if ($type == 'sonar') {
        preg_match_all('/5-0-(\d+)-\d=(\d+\.\d+)/', $context, $matches);
    }
    elseif ($type == 'avail') {
        preg_match_all('/1-3-(\d+)-\d=(\d+\.\d+)/', $context, $matches);
    }
    elseif ($type == 'rtt') {
        preg_match_all('/1-0-(\d+)-\d=(\d+\.\d+)/', $context, $matches);
    }
    else {
        throw Exception("Unexpected type: $type");
    }
    //print("\nMatches:" . print_r($matches, true));
    for ($i = 0; $i < count($matches[1]); $i++) {
        $result[$aliases[$matches[1][$i]]] = floatval($matches[2][$i]);
    }
    //print("\nResult:" . print_r($result, true));
    return $result;
}

?>