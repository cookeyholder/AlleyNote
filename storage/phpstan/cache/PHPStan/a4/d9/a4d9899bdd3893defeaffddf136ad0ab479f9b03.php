<?php declare(strict_types = 1);

// odsl-/var/www/html/tests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/tests/UI/PostUITest.php' => 
    array (
      0 => 'df80b6e869261308c16e875f6fa41f2f9e25dfb6',
      1 => 
      array (
        0 => 'tests\\ui\\postuitest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\shoulddisplaypostlist',
        1 => 'tests\\ui\\shouldcreatenewpost',
        2 => 'tests\\ui\\shouldeditexistingpost',
        3 => 'tests\\ui\\shoulddeletepost',
        4 => 'tests\\ui\\shouldhandleresponsivelayout',
        5 => 'tests\\ui\\shouldsupportdarkmode',
        6 => 'tests\\ui\\login',
        7 => 'tests\\ui\\browseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/UITestCase.php' => 
    array (
      0 => '4dbd983bf464b60bbb8db282922a3ce4ec5fb150',
      1 => 
      array (
        0 => 'tests\\ui\\uitestcase',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\setupbeforeclass',
        1 => 'tests\\ui\\teardownafterclass',
        2 => 'tests\\ui\\capturescreenshot',
        3 => 'tests\\ui\\assertelementvisible',
        4 => 'tests\\ui\\assertelementnotvisible',
        5 => 'tests\\ui\\asserttextpresent',
        6 => 'tests\\ui\\asserttextnotpresent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/CrossBrowserTest.php' => 
    array (
      0 => '0bd4920423b10b1043f49cea34f1991ef0e9f918',
      1 => 
      array (
        0 => 'tests\\ui\\crossbrowsertest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\browseraction',
        1 => 'tests\\ui\\testbrowsercompatibility',
        2 => 'tests\\ui\\testbasicfunctionality',
        3 => 'tests\\ui\\testcssstyles',
        4 => 'tests\\ui\\testjavascriptfeatures',
        5 => 'tests\\ui\\testresponsivedesign',
        6 => 'tests\\ui\\performbrowseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/UserExperienceTest.php' => 
    array (
      0 => 'dfd5a3dd7b2028edf311e251f5da36fa0e537a43',
      1 => 
      array (
        0 => 'tests\\ui\\userexperiencetest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\shouldmeetaccessibilitystandards',
        1 => 'tests\\ui\\shouldprovidegooduserinteraction',
        2 => 'tests\\ui\\shouldperformwell',
        3 => 'tests\\ui\\shouldhandleerrors',
        4 => 'tests\\ui\\testforminteraction',
        5 => 'tests\\ui\\testerrorhandling',
        6 => 'tests\\ui\\testloadingstates',
        7 => 'tests\\ui\\testtooltips',
        8 => 'tests\\ui\\testscrollperformance',
        9 => 'tests\\ui\\testdynamicloading',
        10 => 'tests\\ui\\testnetworkerrorhandling',
        11 => 'tests\\ui\\testformvalidationerrors',
        12 => 'tests\\ui\\testnotfoundpage',
        13 => 'tests\\ui\\browseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Database/DatabaseConnectionTest.php' => 
    array (
      0 => 'e4b9c399594011e4959747d54bf103212a07fba5',
      1 => 
      array (
        0 => 'tests\\unit\\database\\databaseconnectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\database\\setup',
        1 => 'tests\\unit\\database\\createssingletonpdoinstance',
        2 => 'tests\\unit\\database\\executesquerysuccessfully',
        3 => 'tests\\unit\\database\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/PostRepositoryPerformanceTest.php' => 
    array (
      0 => '7aa3e4a7458ef45c29f055dc12f54fe913f728c5',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\postrepositoryperformancetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testbulkinsertperformance',
        3 => 'tests\\unit\\repository\\testpaginationperformance',
        4 => 'tests\\unit\\repository\\testsearchperformance',
        5 => 'tests\\unit\\repository\\testmultipletagassignmentperformance',
        6 => 'tests\\unit\\repository\\testconcurrentviewsincrementperformance',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/PostRepositoryTest.php' => 
    array (
      0 => '5fa833e1686278a987caad522268c1bc5893d372',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\postrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testcancreatepost',
        3 => 'tests\\unit\\repository\\testcanfindpostbyid',
        4 => 'tests\\unit\\repository\\testcanfindpostbyuuid',
        5 => 'tests\\unit\\repository\\testcanupdatepost',
        6 => 'tests\\unit\\repository\\testcandeletepost',
        7 => 'tests\\unit\\repository\\testcanpaginateposts',
        8 => 'tests\\unit\\repository\\testcangetpinnedposts',
        9 => 'tests\\unit\\repository\\testcansetpinnedstatus',
        10 => 'tests\\unit\\repository\\testcanincrementviews',
        11 => 'tests\\unit\\repository\\testshouldrollbackontagassignmenterror',
        12 => 'tests\\unit\\repository\\testshouldcommitontagassignmentsuccess',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/UserRepositoryTest.php' => 
    array (
      0 => '9a583ae431abee438e77269285a0a0d668115332',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\userrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\setuptestdatabase',
        2 => 'tests\\unit\\repository\\createusersuccessfully',
        3 => 'tests\\unit\\repository\\updateusersuccessfully',
        4 => 'tests\\unit\\repository\\deleteusersuccessfully',
        5 => 'tests\\unit\\repository\\finduserbyuuid',
        6 => 'tests\\unit\\repository\\finduserbyusername',
        7 => 'tests\\unit\\repository\\finduserbyemail',
        8 => 'tests\\unit\\repository\\preventduplicateusername',
        9 => 'tests\\unit\\repository\\preventduplicateemail',
        10 => 'tests\\unit\\repository\\finduserbyid',
        11 => 'tests\\unit\\repository\\returnnullwhenusernotfound',
        12 => 'tests\\unit\\repository\\updatelastlogintime',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/IpRepositoryTest.php' => 
    array (
      0 => 'fb0d66f89a70cd9e00659e2109a359fcf973e0d8',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\iprepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testcancreateiprule',
        3 => 'tests\\unit\\repository\\testcannotcreateinvalidipaddress',
        4 => 'tests\\unit\\repository\\testcancreatecidrrange',
        5 => 'tests\\unit\\repository\\testfindbyipaddress',
        6 => 'tests\\unit\\repository\\testgetbytype',
        7 => 'tests\\unit\\repository\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Cache/CacheManagerTest.php' => 
    array (
      0 => '6fd964f4a52319e8a836b8ab285e3b42366fa916',
      1 => 
      array (
        0 => 'tests\\unit\\cache\\cachemanagertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\cache\\setup',
        1 => 'tests\\unit\\cache\\testsetandget',
        2 => 'tests\\unit\\cache\\testgetwithdefault',
        3 => 'tests\\unit\\cache\\testhas',
        4 => 'tests\\unit\\cache\\testdelete',
        5 => 'tests\\unit\\cache\\testclear',
        6 => 'tests\\unit\\cache\\testremember',
        7 => 'tests\\unit\\cache\\testrememberforever',
        8 => 'tests\\unit\\cache\\testttlexpiration',
        9 => 'tests\\unit\\cache\\testmany',
        10 => 'tests\\unit\\cache\\testputmany',
        11 => 'tests\\unit\\cache\\testdeletepattern',
        12 => 'tests\\unit\\cache\\testincrement',
        13 => 'tests\\unit\\cache\\testdecrement',
        14 => 'tests\\unit\\cache\\testgetstats',
        15 => 'tests\\unit\\cache\\testcleanup',
        16 => 'tests\\unit\\cache\\testisvalidkey',
        17 => 'tests\\unit\\cache\\testcomplexdatatypes',
        18 => 'tests\\unit\\cache\\testdefaultttl',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Cache/CacheKeysTest.php' => 
    array (
      0 => '79d9c96e603d95ff0b1be7971d844bb5951c5004',
      1 => 
      array (
        0 => 'tests\\unit\\cache\\cachekeystest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\cache\\testpostcachekey',
        1 => 'tests\\unit\\cache\\testpostbyuuidcachekey',
        2 => 'tests\\unit\\cache\\testpostlistcachekey',
        3 => 'tests\\unit\\cache\\testpinnedpostscachekey',
        4 => 'tests\\unit\\cache\\testpostsbycategorycachekey',
        5 => 'tests\\unit\\cache\\testuserpostscachekey',
        6 => 'tests\\unit\\cache\\testposttagscachekey',
        7 => 'tests\\unit\\cache\\testpostviewscachekey',
        8 => 'tests\\unit\\cache\\testusercachekey',
        9 => 'tests\\unit\\cache\\testuserbyemailcachekey',
        10 => 'tests\\unit\\cache\\testsystemconfigcachekey',
        11 => 'tests\\unit\\cache\\testtagpostscachekey',
        12 => 'tests\\unit\\cache\\testsearchresultscachekey',
        13 => 'tests\\unit\\cache\\testratelimitbyipcachekey',
        14 => 'tests\\unit\\cache\\testratelimitbyusercachekey',
        15 => 'tests\\unit\\cache\\testgetprefix',
        16 => 'tests\\unit\\cache\\testgetseparator',
        17 => 'tests\\unit\\cache\\testisvalidkeywithvalidkey',
        18 => 'tests\\unit\\cache\\testisvalidkeywithinvalidkey',
        19 => 'tests\\unit\\cache\\testparsevalidkey',
        20 => 'tests\\unit\\cache\\testparseinvalidkey',
        21 => 'tests\\unit\\cache\\testpatterngeneration',
        22 => 'tests\\unit\\cache\\testuserpattern',
        23 => 'tests\\unit\\cache\\testpostpattern',
        24 => 'tests\\unit\\cache\\testpostslistpattern',
        25 => 'tests\\unit\\cache\\teststatspattern',
        26 => 'tests\\unit\\cache\\testdailystatscachekey',
        27 => 'tests\\unit\\cache\\testmonthlystatscachekey',
        28 => 'tests\\unit\\cache\\testcachekeyconsistency',
        29 => 'tests\\unit\\cache\\testcachekeyuniqueness',
        30 => 'tests\\unit\\cache\\testemptyparameterhandling',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/ExampleTest.php' => 
    array (
      0 => '70bcefac6518229d96177306687e327f43d3d3f3',
      1 => 
      array (
        0 => 'tests\\unit\\exampletest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\testbasictest',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repositories/AttachmentRepositoryTest.php' => 
    array (
      0 => 'e7dd819c0dc7fc13b1176b022cba16372aa8df8f',
      1 => 
      array (
        0 => 'tests\\unit\\repositories\\attachmentrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repositories\\setup',
        1 => 'tests\\unit\\repositories\\createtesttables',
        2 => 'tests\\unit\\repositories\\shouldcreateattachmentsuccessfully',
        3 => 'tests\\unit\\repositories\\shouldfindattachmentbyid',
        4 => 'tests\\unit\\repositories\\shouldfindattachmentbyuuid',
        5 => 'tests\\unit\\repositories\\shouldreturnnullfornonexistentid',
        6 => 'tests\\unit\\repositories\\shouldreturnnullfornonexistentuuid',
        7 => 'tests\\unit\\repositories\\shouldgetattachmentsbypostid',
        8 => 'tests\\unit\\repositories\\shouldsoftdeleteattachment',
        9 => 'tests\\unit\\repositories\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Models/PostTest.php' => 
    array (
      0 => '99ea4b54f03e99e1d007d800c5183c3c5fa70dcc',
      1 => 
      array (
        0 => 'tests\\unit\\models\\posttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\correctlyinitializeswithvaliddata',
        1 => 'tests\\unit\\models\\handlesnullablefieldscorrectly',
        2 => 'tests\\unit\\models\\setsdefaultvaluescorrectly',
        3 => 'tests\\unit\\models\\storesrawhtmlintitleandcontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Exceptions/PostNotFoundExceptionTest.php' => 
    array (
      0 => 'e391d3f06b4208cc29fedf05c603945a49ba0379',
      1 => 
      array (
        0 => 'tests\\unit\\exceptions\\postnotfoundexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\exceptions\\testconstructorwithpostid',
        1 => 'tests\\unit\\exceptions\\testconstructorwithcustommessage',
        2 => 'tests\\unit\\exceptions\\testbyidstaticmethod',
        3 => 'tests\\unit\\exceptions\\testbyuuidstaticmethod',
        4 => 'tests\\unit\\exceptions\\testinheritsfromnotfoundexception',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/Post/CreatePostDTOTest.php' => 
    array (
      0 => 'f2ba9cc1b146c247d379c2fdaddcefc8e9e5a6e8',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\createpostdtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\setup',
        1 => 'tests\\unit\\dtos\\post\\testcancreatedtowithvaliddata',
        2 => 'tests\\unit\\dtos\\post\\testcancreatedtowithminimaldata',
        3 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpublishdate',
        4 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpinnedpost',
        5 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingtitle',
        6 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptytitle',
        7 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionfortitletoolong',
        8 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionfortitlewithonlywhitespace',
        9 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingcontent',
        10 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptycontent',
        11 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforcontentwithonlywhitespace',
        12 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvaliduserid',
        13 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissinguserid',
        14 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidip',
        15 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingip',
        16 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidstatus',
        17 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidpublishdate',
        18 => 'tests\\unit\\dtos\\post\\testacceptsvalidipv6address',
        19 => 'tests\\unit\\dtos\\post\\testacceptsallvalidpoststatuses',
        20 => 'tests\\unit\\dtos\\post\\testhandlesbooleanvalues',
        21 => 'tests\\unit\\dtos\\post\\testtoarrayreturnscorrectformat',
        22 => 'tests\\unit\\dtos\\post\\testjsonserializationworks',
        23 => 'tests\\unit\\dtos\\post\\testacceptsrfc3339datetimeformats',
        24 => 'tests\\unit\\dtos\\post\\testtrimstitleandcontent',
        25 => 'tests\\unit\\dtos\\post\\testhandlesemptypublishdate',
        26 => 'tests\\unit\\dtos\\post\\testvalidatesunicodecontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Http/ApiResponseTest.php' => 
    array (
      0 => '934be51b4dcc614bbf594b8625c54f48261c6266',
      1 => 
      array (
        0 => 'tests\\unit\\http\\apiresponsetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\http\\testsuccessresponse',
        1 => 'tests\\unit\\http\\testsuccessresponsewithdefaults',
        2 => 'tests\\unit\\http\\testerrorresponse',
        3 => 'tests\\unit\\http\\testerrorresponsewithdefaults',
        4 => 'tests\\unit\\http\\testpaginatedresponse',
        5 => 'tests\\unit\\http\\testtimestampformat',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Controllers/IpControllerTest.php' => 
    array (
      0 => '0d93398d9c794f23a4ca0d2cb234f0123af02524',
      1 => 
      array (
        0 => 'tests\\unit\\controllers\\ipcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\controllers\\setup',
        1 => 'tests\\unit\\controllers\\testcancreateiprule',
        2 => 'tests\\unit\\controllers\\testcannotcreatewithinvaliddata',
        3 => 'tests\\unit\\controllers\\testcanlistrulesbytype',
        4 => 'tests\\unit\\controllers\\testcancheckipaccess',
        5 => 'tests\\unit\\controllers\\testcannotcheckinvalidip',
        6 => 'tests\\unit\\controllers\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Factory/PostFactoryTest.php' => 
    array (
      0 => 'db741274ab66072c13df8a0812e175d47684f5cc',
      1 => 
      array (
        0 => 'tests\\unit\\factory\\postfactorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\factory\\testitcanmakepostdata',
        1 => 'tests\\unit\\factory\\testitcancreatepostindatabase',
        2 => 'tests\\unit\\factory\\testitcanoverridedefaultattributes',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/XssProtectionServiceTest.php' => 
    array (
      0 => 'f0916640cacf0cee017aa2911fa6f37fe85b045c',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\xssprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\escapesbasichtml',
        2 => 'tests\\unit\\services\\security\\escapeshtmlattributes',
        3 => 'tests\\unit\\services\\security\\handlesnullinput',
        4 => 'tests\\unit\\services\\security\\cleansarrayofstrings',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/SessionSecurityServiceTest.php' => 
    array (
      0 => 'bb6d39fcd4cfc0e85403033fdce873c55bb2c025',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\sessionsecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\initializessecuresessioninproduction',
        3 => 'tests\\unit\\services\\security\\initializessecuresessionindevelopment',
        4 => 'tests\\unit\\services\\security\\setsusersessionwithuseragentbinding',
        5 => 'tests\\unit\\services\\security\\validatesuseragentcorrectly',
        6 => 'tests\\unit\\services\\security\\validatessessionipcorrectly',
        7 => 'tests\\unit\\services\\security\\performscomprehensivesecuritycheck',
        8 => 'tests\\unit\\services\\security\\detectsuseragentchange',
        9 => 'tests\\unit\\services\\security\\detectsipchange',
        10 => 'tests\\unit\\services\\security\\handlesipverificationflow',
        11 => 'tests\\unit\\services\\security\\detectsexpiredsession',
        12 => 'tests\\unit\\services\\security\\updatesactivitytime',
        13 => 'tests\\unit\\services\\security\\destroyssessionsecurely',
        14 => 'tests\\unit\\services\\security\\regeneratessessionid',
        15 => 'tests\\unit\\services\\security\\handlesmissingsessiondata',
        16 => 'tests\\unit\\services\\security\\handlesipverificationtimeout',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/LoggingSecurityServiceTest.php' => 
    array (
      0 => '3872d457bf7a2a91e5a04eb6dda45e443721161b',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\loggingsecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\storage_path',
        2 => 'tests\\unit\\services\\security\\teardown',
        3 => 'tests\\unit\\services\\security\\recursivedelete',
        4 => 'tests\\unit\\services\\security\\sanitizescontextdatacorrectly',
        5 => 'tests\\unit\\services\\security\\appliesrequestwhitelistcorrectly',
        6 => 'tests\\unit\\services\\security\\truncateslongstrings',
        7 => 'tests\\unit\\services\\security\\enrichessecuritycontext',
        8 => 'tests\\unit\\services\\security\\enrichesrequestcontext',
        9 => 'tests\\unit\\services\\security\\logssecurityeventscorrectly',
        10 => 'tests\\unit\\services\\security\\logsrequestswithwhitelist',
        11 => 'tests\\unit\\services\\security\\handlessensitivefieldvariations',
        12 => 'tests\\unit\\services\\security\\handlesemptyandnullvalues',
        13 => 'tests\\unit\\services\\security\\returnslogstatistics',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/PwnedPasswordServiceTest.php' => 
    array (
      0 => 'fc4d59dbfa61ea6330fdf1bd885ec3a0d9fca2c1',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\pwnedpasswordservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\testshoulddetectcommonpwnedpassword',
        2 => 'tests\\unit\\services\\security\\testshouldnotdetectsecurepassword',
        3 => 'tests\\unit\\services\\security\\testshouldhandleapifailuregracefully',
        4 => 'tests\\unit\\services\\security\\testshouldvalidateapistatus',
        5 => 'tests\\unit\\services\\security\\testshouldhandlemultiplepasswords',
        6 => 'tests\\unit\\services\\security\\testshouldcacheresults',
        7 => 'tests\\unit\\services\\security\\testshouldclearcache',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/CsrfProtectionServiceTest.php' => 
    array (
      0 => 'b54edfdd3c1213c751f79e0f4e97305de2e903e8',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\csrfprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\generatesvalidtoken',
        3 => 'tests\\unit\\services\\security\\validatescorrecttoken',
        4 => 'tests\\unit\\services\\security\\throwsexceptionforemptytoken',
        5 => 'tests\\unit\\services\\security\\throwsexceptionforinvalidtoken',
        6 => 'tests\\unit\\services\\security\\throwsexceptionforexpiredtoken',
        7 => 'tests\\unit\\services\\security\\updatestokenaftersuccessfulvalidation',
        8 => 'tests\\unit\\services\\security\\initializestokenpool',
        9 => 'tests\\unit\\services\\security\\supportsmultiplevalidtokensinpool',
        10 => 'tests\\unit\\services\\security\\validatestokenfrompoolwithconstanttimecomparison',
        11 => 'tests\\unit\\services\\security\\removestokenfrompoolafteruse',
        12 => 'tests\\unit\\services\\security\\cleansexpiredtokensfrompool',
        13 => 'tests\\unit\\services\\security\\limitstokenpoolsize',
        14 => 'tests\\unit\\services\\security\\gettokenpoolstatusreturnscorrectinfo',
        15 => 'tests\\unit\\services\\security\\fallsbacktosingletokenmodewhenpoolnotinitialized',
        16 => 'tests\\unit\\services\\security\\istokenvalidreturnsfalseforinvalidtoken',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/PostServiceTest.php' => 
    array (
      0 => '64d058af3930735a0e272314e744221b994e77cb',
      1 => 
      array (
        0 => 'tests\\unit\\services\\postservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testcreatepostwithvaliddto',
        2 => 'tests\\unit\\services\\testupdatepostwithvaliddto',
        3 => 'tests\\unit\\services\\testupdatepostwithinvalidstatustransition',
        4 => 'tests\\unit\\services\\testupdatenonexistentpost',
        5 => 'tests\\unit\\services\\testupdatepostwithnochanges',
        6 => 'tests\\unit\\services\\testdeletepost',
        7 => 'tests\\unit\\services\\testdeletepostwithrepositoryexception',
        8 => 'tests\\unit\\services\\testdeletepostwithgeneralexception',
        9 => 'tests\\unit\\services\\testfindbyid',
        10 => 'tests\\unit\\services\\testfindbyidnotfound',
        11 => 'tests\\unit\\services\\testlistposts',
        12 => 'tests\\unit\\services\\testgetpinnedposts',
        13 => 'tests\\unit\\services\\testsetpinned',
        14 => 'tests\\unit\\services\\testsetpinnedwithrepositoryexception',
        15 => 'tests\\unit\\services\\testsetpinnedwithgeneralexception',
        16 => 'tests\\unit\\services\\testsettags',
        17 => 'tests\\unit\\services\\testsettagswithnonexistentpost',
        18 => 'tests\\unit\\services\\testrecordview',
        19 => 'tests\\unit\\services\\testrecordviewwithinvalidip',
        20 => 'tests\\unit\\services\\testrecordviewfornonpublishedpost',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Enums/PostStatusTest.php' => 
    array (
      0 => 'db1240b19a345ebb2e5a92128f9d1d0a251564eb',
      1 => 
      array (
        0 => 'tests\\unit\\services\\enums\\poststatustest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\enums\\testgetlabel',
        1 => 'tests\\unit\\services\\enums\\testcantransitionto',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/CacheServiceTest.php' => 
    array (
      0 => 'f8544f77cb87d0645cd35026ff9eb18e8c18263b',
      1 => 
      array (
        0 => 'tests\\unit\\services\\cacheservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\storeandretrievedata',
        2 => 'tests\\unit\\services\\handleconnectionfailure',
        3 => 'tests\\unit\\services\\handleconcurrentrequests',
        4 => 'tests\\unit\\services\\clearcache',
        5 => 'tests\\unit\\services\\deletespecifickey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AuthServiceTest.php' => 
    array (
      0 => '26572e04437f21985157e84de8ad57ba8f6ddb21',
      1 => 
      array (
        0 => 'tests\\unit\\services\\authservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\teardown',
        2 => 'tests\\unit\\services\\it_should_register_new_user_successfully',
        3 => 'tests\\unit\\services\\it_should_validate_registration_data',
        4 => 'tests\\unit\\services\\it_should_login_user_successfully',
        5 => 'tests\\unit\\services\\it_should_fail_login_with_invalid_credentials',
        6 => 'tests\\unit\\services\\it_should_not_login_inactive_user',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AttachmentServiceTest.php' => 
    array (
      0 => 'fb5e60465fa26324a773e24430102b4f44feb76d',
      1 => 
      array (
        0 => 'tests\\unit\\services\\attachmentservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\shoulduploadfilesuccessfully',
        2 => 'tests\\unit\\services\\shouldrejectinvalidfiletype',
        3 => 'tests\\unit\\services\\shouldrejectoversizedfile',
        4 => 'tests\\unit\\services\\shouldrejectuploadtononexistentpost',
        5 => 'tests\\unit\\services\\createuploadedfilemock',
        6 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/IpServiceTest.php' => 
    array (
      0 => '6b9c1119410f076276d8d6a9b8a7c5bb00bd021f',
      1 => 
      array (
        0 => 'tests\\unit\\services\\ipservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testcancreateiprule',
        2 => 'tests\\unit\\services\\testcannotcreateinvalidiprule',
        3 => 'tests\\unit\\services\\testcannotcreatewithinvalidtype',
        4 => 'tests\\unit\\services\\testcancheckipaccess',
        5 => 'tests\\unit\\services\\testcanvalidatecidrrange',
        6 => 'tests\\unit\\services\\testcangetrulesbytype',
        7 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/RateLimitServiceTest.php' => 
    array (
      0 => 'c3c1820cf82ed45b0f83ce5f8b38d5b05852f436',
      1 => 
      array (
        0 => 'tests\\unit\\services\\ratelimitservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\shouldallowfirstrequest',
        2 => 'tests\\unit\\services\\shouldrejectwhenlimitexceeded',
        3 => 'tests\\unit\\services\\shouldhandlecachefailuregracefully',
        4 => 'tests\\unit\\services\\shouldincrementrequestcount',
        5 => 'tests\\unit\\services\\shouldhandlesetfailure',
        6 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/CsrfProtectionTest.php' => 
    array (
      0 => '7025d602de6a57b84bb2da36e9a978d7a12d76ef',
      1 => 
      array (
        0 => 'tests\\security\\csrfprotectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldrejectrequestwithoutcsrftoken',
        2 => 'tests\\security\\shouldrejectrequestwithinvalidcsrftoken',
        3 => 'tests\\security\\shouldacceptrequestwithvalidcsrftoken',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/SqlInjectionTest.php' => 
    array (
      0 => 'd7be013afe0d9155cccc0c14a080785c230179c3',
      1 => 
      array (
        0 => 'tests\\security\\sqlinjectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\shouldpreventsqlinjectionintitlesearch',
        3 => 'tests\\security\\shouldhandlespecialcharactersincontent',
        4 => 'tests\\security\\shouldpreventsqlinjectioninuseridfilter',
        5 => 'tests\\security\\shouldsanitizesearchinput',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/XssPreventionTest.php' => 
    array (
      0 => '075c58420224b3324bb1edf72421730b6bfde4c1',
      1 => 
      array (
        0 => 'tests\\security\\xsspreventiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldescapehtmlinposttitle',
        2 => 'tests\\security\\shouldescapehtmlinpostcontent',
        3 => 'tests\\security\\shouldhandleencodedxssattempts',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/FileUploadSecurityTest.php' => 
    array (
      0 => '2e985e15fbd0ef614294144c3d1c3b773d5ce636',
      1 => 
      array (
        0 => 'tests\\security\\fileuploadsecuritytest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldrejectexecutablefiles',
        2 => 'tests\\security\\shouldrejectdoubleextensionfiles',
        3 => 'tests\\security\\shouldrejectoversizedfiles',
        4 => 'tests\\security\\shouldrejectmaliciousmimetypes',
        5 => 'tests\\security\\shouldpreventpathtraversal',
        6 => 'tests\\security\\shouldacceptvalidfiles',
        7 => 'tests\\security\\createuploadedfilemock',
        8 => 'tests\\security\\teardown',
        9 => 'tests\\security\\removedirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/PasswordHashingTest.php' => 
    array (
      0 => 'f09fb63cd1955bdf8f62bdff23a859a55d9ff971',
      1 => 
      array (
        0 => 'tests\\security\\passwordhashingtest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\shouldhashpasswordusingargon2id',
        3 => 'tests\\security\\shoulduseappropriatehashingoptions',
        4 => 'tests\\security\\shouldrejectweakpasswords',
        5 => 'tests\\security\\shouldpreventpasswordreuse',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/RateLimitTest.php' => 
    array (
      0 => 'd252b0abc6f01a3398d8293b81e0ec4e27165c5d',
      1 => 
      array (
        0 => 'tests\\integration\\ratelimittest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\shouldlimitratesuccessfully',
        2 => 'tests\\integration\\shouldresetlimitaftertimewindow',
        3 => 'tests\\integration\\shouldhandledifferentipsindependently',
        4 => 'tests\\integration\\shouldhandleserviceunavailability',
        5 => 'tests\\integration\\shouldincrementcountercorrectly',
        6 => 'tests\\integration\\shouldhandlemaxattemptsreached',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AuthControllerTest.php' => 
    array (
      0 => '65b88b7285ecc4eda9bcf04a2cf1985389a130b2',
      1 => 
      array (
        0 => 'tests\\integration\\authcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\registerusersuccessfully',
        3 => 'tests\\integration\\returnvalidationerrorsforinvalidregistrationdata',
        4 => 'tests\\integration\\loginusersuccessfully',
        5 => 'tests\\integration\\returnerrorforinvalidlogin',
        6 => 'tests\\integration\\logoutusersuccessfully',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Repositories/PostRepositoryTest.php' => 
    array (
      0 => 'e0e2ace5a8c4aeb8da68f26ee0ea455aeb6c73d6',
      1 => 
      array (
        0 => 'tests\\integration\\repositories\\postrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\repositories\\setup',
        1 => 'tests\\integration\\repositories\\teardown',
        2 => 'tests\\integration\\repositories\\createtesttables',
        3 => 'tests\\integration\\repositories\\createtestpost',
        4 => 'tests\\integration\\repositories\\testcreatepost',
        5 => 'tests\\integration\\repositories\\testfindpostbyid',
        6 => 'tests\\integration\\repositories\\testfindnonexistentpost',
        7 => 'tests\\integration\\repositories\\testfindpostbyuuid',
        8 => 'tests\\integration\\repositories\\testfindpostbyseqnumber',
        9 => 'tests\\integration\\repositories\\testupdatepost',
        10 => 'tests\\integration\\repositories\\testsoftdeletepost',
        11 => 'tests\\integration\\repositories\\testpaginateposts',
        12 => 'tests\\integration\\repositories\\testgetpinnedposts',
        13 => 'tests\\integration\\repositories\\testsetpinned',
        14 => 'tests\\integration\\repositories\\testincrementviews',
        15 => 'tests\\integration\\repositories\\testincrementviewswithinvalidip',
        16 => 'tests\\integration\\repositories\\testsettags',
        17 => 'tests\\integration\\repositories\\testgetpostsbytag',
        18 => 'tests\\integration\\repositories\\testsecurityvalidationfordisallowedfields',
        19 => 'tests\\integration\\repositories\\testdeletedpostsarenotreturned',
        20 => 'tests\\integration\\repositories\\testtransactionrollbackonerror',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AttachmentControllerTest.php' => 
    array (
      0 => '75f4dab6fc8e34c1389e5bbb3146347e3c883ded',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\uploadshouldstorefilesuccessfully',
        2 => 'tests\\integration\\uploadshouldreturn400forinvalidfile',
        3 => 'tests\\integration\\listshouldreturnattachments',
        4 => 'tests\\integration\\deleteshouldremoveattachment',
        5 => 'tests\\integration\\deleteshouldreturn404fornonexistentattachment',
        6 => 'tests\\integration\\deleteshouldreturn400forinvaliduuid',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/PostControllerTest.php' => 
    array (
      0 => '167cbee941d723d07f54587af20b08c2e794cf6e',
      1 => 
      array (
        0 => 'tests\\integration\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\indexshouldreturnpaginatedposts',
        2 => 'tests\\integration\\showshouldreturnpostdetails',
        3 => 'tests\\integration\\storeshouldcreatenewpost',
        4 => 'tests\\integration\\storeshouldreturn400whenvalidationfails',
        5 => 'tests\\integration\\updateshouldmodifyexistingpost',
        6 => 'tests\\integration\\updateshouldreturn404whenpostnotfound',
        7 => 'tests\\integration\\destroyshoulddeletepost',
        8 => 'tests\\integration\\updatepinstatusshouldupdatepinstatus',
        9 => 'tests\\integration\\updatepinstatusshouldreturn422wheninvalidstatetransition',
        10 => 'tests\\integration\\teardown',
        11 => 'tests\\integration\\createrequestmock',
        12 => 'tests\\integration\\createstreammock',
        13 => 'tests\\integration\\createresponsemock',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Http/PostControllerTest.php' => 
    array (
      0 => 'd3d8e44a8b31888e6e72a3a2819b28463868bcce',
      1 => 
      array (
        0 => 'tests\\integration\\http\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\http\\setup',
        1 => 'tests\\integration\\http\\setupresponsemocks',
        2 => 'tests\\integration\\http\\testgetpostsreturnssuccessresponse',
        3 => 'tests\\integration\\http\\testgetpostswithpaginationparameters',
        4 => 'tests\\integration\\http\\testgetpostswithsearchfilter',
        5 => 'tests\\integration\\http\\testgetpostswithstatusfilter',
        6 => 'tests\\integration\\http\\testgetpostswithinvalidlimitreturnsposts',
        7 => 'tests\\integration\\http\\testcreatepostwithvaliddata',
        8 => 'tests\\integration\\http\\testcreatepostwithinvalidjsonreturnserror',
        9 => 'tests\\integration\\http\\testcreatepostwithmissingrequiredfields',
        10 => 'tests\\integration\\http\\testgetpostbyidreturnssuccess',
        11 => 'tests\\integration\\http\\testgetnonexistentpostreturnsnotfound',
        12 => 'tests\\integration\\http\\testgetpostwithinvalididreturnserror',
        13 => 'tests\\integration\\http\\testupdatepostwithvaliddata',
        14 => 'tests\\integration\\http\\testupdatenonexistentpostreturnsnotfound',
        15 => 'tests\\integration\\http\\testdeletepost',
        16 => 'tests\\integration\\http\\testdeletenonexistentpostreturnsnotfound',
        17 => 'tests\\integration\\http\\testtogglepostpin',
        18 => 'tests\\integration\\http\\testtogglepostpinwithinvaliddata',
        19 => 'tests\\integration\\http\\testapiresponsestructureconsistency',
        20 => 'tests\\integration\\http\\testhealthendpoint',
        21 => 'tests\\integration\\http\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/FileSystemBackupTest.php' => 
    array (
      0 => '819d46a7f08233d9a464c5c36cca8d9b682297c3',
      1 => 
      array (
        0 => 'tests\\integration\\filesystembackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtestfiles',
        2 => 'tests\\integration\\backupfilessuccessfully',
        3 => 'tests\\integration\\restorefilessuccessfully',
        4 => 'tests\\integration\\handlebackuperrorsgracefully',
        5 => 'tests\\integration\\handlerestoreerrorsgracefully',
        6 => 'tests\\integration\\handlepermissionerrors',
        7 => 'tests\\integration\\maintainfilemetadataduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DatabaseBackupTest.php' => 
    array (
      0 => 'f6f7684823a6ed3c654a3f2da9eb934170389305',
      1 => 
      array (
        0 => 'tests\\integration\\databasebackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtesttables',
        2 => 'tests\\integration\\inserttestdata',
        3 => 'tests\\integration\\backupdatabasesuccessfully',
        4 => 'tests\\integration\\restoredatabasesuccessfully',
        5 => 'tests\\integration\\handlebackuperrorsgracefully',
        6 => 'tests\\integration\\handlerestoreerrorsgracefully',
        7 => 'tests\\integration\\maintaindataintegrityduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AttachmentUploadTest.php' => 
    array (
      0 => '2c8cd306d5641a8691184fd69209c83d474d06c3',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentuploadtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createuploadedfilemock',
        2 => 'tests\\integration\\should_handle_concurrent_uploads',
        3 => 'tests\\integration\\should_handle_large_file_upload',
        4 => 'tests\\integration\\should_validate_file_types',
        5 => 'tests\\integration\\should_handle_disk_full_error',
        6 => 'tests\\integration\\should_handle_permission_error',
        7 => 'tests\\integration\\teardown',
        8 => 'tests\\integration\\createtesttables',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/TestCase.php' => 
    array (
      0 => '9f64e6176eaa977a5c9ee64042f31f0209fbd5ed',
      1 => 
      array (
        0 => 'tests\\testcase',
      ),
      2 => 
      array (
        0 => 'tests\\setup',
        1 => 'tests\\createtesttables',
        2 => 'tests\\createindices',
        3 => 'tests\\createresponsemock',
        4 => 'tests\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/Post/UpdatePostDTOTest.php' => 
    array (
      0 => 'bdf3b8da4d0c3a045870b476fe9936e790177b28',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\updatepostdtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\setup',
        1 => 'tests\\unit\\dtos\\post\\testcancreatedtowithfullupdate',
        2 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpartialupdate',
        3 => 'tests\\unit\\dtos\\post\\testcancreatedtowithcontentonlyupdate',
        4 => 'tests\\unit\\dtos\\post\\testcancreatedtowithstatusonlyupdate',
        5 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpinnedonlyupdate',
        6 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpublishdateonlyupdate',
        7 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithnodata',
        8 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithonlynullvalues',
        9 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithonlyemptystrings',
        10 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidtitle',
        11 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptytitlecontent',
        12 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptycontentcontent',
        13 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidstatus',
        14 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidpublishdate',
        15 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidbooleanvalue',
        16 => 'tests\\unit\\dtos\\post\\testacceptsallvalidpoststatuses',
        17 => 'tests\\unit\\dtos\\post\\testhandlesbooleanvalues',
        18 => 'tests\\unit\\dtos\\post\\testtoarrayreturnsonlychangedfields',
        19 => 'tests\\unit\\dtos\\post\\testtoarrayreturnsemptyarraywhennochanges',
        20 => 'tests\\unit\\dtos\\post\\testtoarraywithallfields',
        21 => 'tests\\unit\\dtos\\post\\testhaschangesreturnstruewhendataexists',
        22 => 'tests\\unit\\dtos\\post\\testhaschangesreturnsfalsewhennodata',
        23 => 'tests\\unit\\dtos\\post\\testjsonserializationworks',
        24 => 'tests\\unit\\dtos\\post\\testjsonserializationwithemptydto',
        25 => 'tests\\unit\\dtos\\post\\testacceptsvalidrfc3339dateformats',
        26 => 'tests\\unit\\dtos\\post\\testhandleswhitespaceinstringfields',
        27 => 'tests\\unit\\dtos\\post\\testgetupdatedfields',
        28 => 'tests\\unit\\dtos\\post\\testhasupdatedfield',
        29 => 'tests\\unit\\dtos\\post\\testhandlesemptypublishdate',
        30 => 'tests\\unit\\dtos\\post\\testvalidatesunicodecontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/BaseDTOTest.php' => 
    array (
      0 => '27423beb10b61f05141bd43b9b2b0f6f12f44e59',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\basedtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\setup',
        1 => 'tests\\unit\\dtos\\teardown',
        2 => 'tests\\unit\\dtos\\createtestdto',
        3 => 'tests\\unit\\dtos\\getvalidationrules',
        4 => 'tests\\unit\\dtos\\toarray',
        5 => 'tests\\unit\\dtos\\testvalidate',
        6 => 'tests\\unit\\dtos\\testgetstring',
        7 => 'tests\\unit\\dtos\\testgetint',
        8 => 'tests\\unit\\dtos\\testgetbool',
        9 => 'tests\\unit\\dtos\\testgetvalue',
        10 => 'tests\\unit\\dtos\\testgetvalidationrules',
        11 => 'tests\\unit\\dtos\\testconstructoracceptsvalidator',
        12 => 'tests\\unit\\dtos\\testtoarrayisimplemented',
        13 => 'tests\\unit\\dtos\\testjsonserializeusestoarray',
        14 => 'tests\\unit\\dtos\\testvalidatecallsvalidatorwithcorrectdata',
        15 => 'tests\\unit\\dtos\\testvalidatethrowsexceptiononvalidationfailure',
        16 => 'tests\\unit\\dtos\\testgetstringreturnscorrectvalue',
        17 => 'tests\\unit\\dtos\\testgetintreturnscorrectvalue',
        18 => 'tests\\unit\\dtos\\testgetboolreturnscorrectvalue',
        19 => 'tests\\unit\\dtos\\testgetvaluereturnscorrectvalue',
        20 => 'tests\\unit\\dtos\\testvalidationrulesareabstract',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/DTOValidationTest.php' => 
    array (
      0 => '3998b6a2504b71e75fec44acc1297f978dbec40c',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\dtovalidationtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\setup',
        1 => 'tests\\unit\\dtos\\test_create_post_dto_validation_scenarios',
        2 => 'tests\\unit\\dtos\\test_create_post_dto_title_validation',
        3 => 'tests\\unit\\dtos\\test_create_post_dto_title_length_limits',
        4 => 'tests\\unit\\dtos\\test_create_post_dto_content_validation',
        5 => 'tests\\unit\\dtos\\test_create_post_dto_user_validation',
        6 => 'tests\\unit\\dtos\\test_create_post_dto_ip_validation',
        7 => 'tests\\unit\\dtos\\test_update_post_dto_partial_validation',
        8 => 'tests\\unit\\dtos\\test_update_post_dto_field_validation',
        9 => 'tests\\unit\\dtos\\test_create_attachment_dto_validation',
        10 => 'tests\\unit\\dtos\\test_create_attachment_dto_file_validation',
        11 => 'tests\\unit\\dtos\\test_register_user_dto_validation',
        12 => 'tests\\unit\\dtos\\test_register_user_dto_username_validation',
        13 => 'tests\\unit\\dtos\\test_register_user_dto_email_validation',
        14 => 'tests\\unit\\dtos\\test_register_user_dto_password_validation',
        15 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_validation',
        16 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_ip_validation',
        17 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_rule_type_validation',
        18 => 'tests\\unit\\dtos\\test_dto_data_sanitization',
        19 => 'tests\\unit\\dtos\\test_dto_validation_performance',
        20 => 'tests\\unit\\dtos\\test_dto_serialization',
        21 => 'tests\\unit\\dtos\\test_dto_edge_cases',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidationExceptionTest.php' => 
    array (
      0 => 'e89074aecb0fade5f43ad04b1c4547f5297c48f7',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\test_create_from_validation_result',
        1 => 'tests\\unit\\validation\\test_create_with_custom_message',
        2 => 'tests\\unit\\validation\\test_empty_validation_result_default_message',
        3 => 'tests\\unit\\validation\\test_create_from_errors',
        4 => 'tests\\unit\\validation\\test_create_from_errors_with_custom_message',
        5 => 'tests\\unit\\validation\\test_create_from_single_error',
        6 => 'tests\\unit\\validation\\test_create_from_single_error_without_rule',
        7 => 'tests\\unit\\validation\\test_get_field_errors',
        8 => 'tests\\unit\\validation\\test_has_field_errors',
        9 => 'tests\\unit\\validation\\test_get_first_error',
        10 => 'tests\\unit\\validation\\test_get_first_field_error',
        11 => 'tests\\unit\\validation\\test_get_all_errors',
        12 => 'tests\\unit\\validation\\test_get_failed_rules',
        13 => 'tests\\unit\\validation\\test_to_api_response',
        14 => 'tests\\unit\\validation\\test_to_debug_array',
        15 => 'tests\\unit\\validation\\test_error_statistics',
        16 => 'tests\\unit\\validation\\test_has_failed_rule',
        17 => 'tests\\unit\\validation\\test_has_field_failed_rule',
        18 => 'tests\\unit\\validation\\test_exception_chaining',
        19 => 'tests\\unit\\validation\\test_json_serialization',
        20 => 'tests\\unit\\validation\\test_performance_with_many_errors',
        21 => 'tests\\unit\\validation\\test_internationalization_support',
        22 => 'tests\\unit\\validation\\test_edge_cases_with_empty_errors',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidatorTest.php' => 
    array (
      0 => '027fcb3854883a7a072bcbf19281155268e6d73c',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validatortest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\setup',
        1 => 'tests\\unit\\validation\\test_required_rule',
        2 => 'tests\\unit\\validation\\test_string_rule',
        3 => 'tests\\unit\\validation\\test_integer_rule',
        4 => 'tests\\unit\\validation\\test_numeric_rule',
        5 => 'tests\\unit\\validation\\test_boolean_rule',
        6 => 'tests\\unit\\validation\\test_email_rule',
        7 => 'tests\\unit\\validation\\test_url_rule',
        8 => 'tests\\unit\\validation\\test_ip_rule',
        9 => 'tests\\unit\\validation\\test_min_rule',
        10 => 'tests\\unit\\validation\\test_max_rule',
        11 => 'tests\\unit\\validation\\test_between_rule',
        12 => 'tests\\unit\\validation\\test_in_rule',
        13 => 'tests\\unit\\validation\\test_not_in_rule',
        14 => 'tests\\unit\\validation\\test_min_length_rule',
        15 => 'tests\\unit\\validation\\test_max_length_rule',
        16 => 'tests\\unit\\validation\\test_length_rule',
        17 => 'tests\\unit\\validation\\test_regex_rule',
        18 => 'tests\\unit\\validation\\test_alpha_rule',
        19 => 'tests\\unit\\validation\\test_alpha_num_rule',
        20 => 'tests\\unit\\validation\\test_alpha_dash_rule',
        21 => 'tests\\unit\\validation\\test_multiple_rules',
        22 => 'tests\\unit\\validation\\test_add_custom_rule',
        23 => 'tests\\unit\\validation\\test_custom_rule_with_parameters',
        24 => 'tests\\unit\\validation\\test_custom_error_messages',
        25 => 'tests\\unit\\validation\\test_validate_or_fail',
        26 => 'tests\\unit\\validation\\test_check_rule_method',
        27 => 'tests\\unit\\validation\\test_stop_on_first_failure',
        28 => 'tests\\unit\\validation\\test_validator_performance',
        29 => 'tests\\unit\\validation\\test_memory_leak',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidationResultTest.php' => 
    array (
      0 => '3d74aa59e5aabd8b7ea18f30fcb29347b67b0530',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validationresulttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\test_create_success_result',
        1 => 'tests\\unit\\validation\\test_create_failure_result',
        2 => 'tests\\unit\\validation\\test_constructor',
        3 => 'tests\\unit\\validation\\test_get_field_errors',
        4 => 'tests\\unit\\validation\\test_has_field_errors',
        5 => 'tests\\unit\\validation\\test_get_first_error',
        6 => 'tests\\unit\\validation\\test_get_first_field_error',
        7 => 'tests\\unit\\validation\\test_get_all_errors',
        8 => 'tests\\unit\\validation\\test_error_count',
        9 => 'tests\\unit\\validation\\test_get_failed_rules',
        10 => 'tests\\unit\\validation\\test_add_error',
        11 => 'tests\\unit\\validation\\test_add_failed_rule',
        12 => 'tests\\unit\\validation\\test_validated_data_access',
        13 => 'tests\\unit\\validation\\test_merge_results',
        14 => 'tests\\unit\\validation\\test_to_array',
        15 => 'tests\\unit\\validation\\test_json_serialization',
        16 => 'tests\\unit\\validation\\test_to_string',
        17 => 'tests\\unit\\validation\\test_empty_results',
        18 => 'tests\\unit\\validation\\test_performance_with_large_data',
        19 => 'tests\\unit\\validation\\test_object_independence',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/PostControllerTest_new.php' => 
    array (
      0 => '0479b4156139402066695c883f2c0f5be601d86a',
      1 => 
      array (
        0 => 'tests\\integration\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\indexshouldreturnpaginatedposts',
        3 => 'tests\\integration\\showshouldreturnpostdetails',
        4 => 'tests\\integration\\storeshouldcreatenewpost',
        5 => 'tests\\integration\\storeshouldreturn400whenvalidationfails',
        6 => 'tests\\integration\\updateshouldmodifyexistingpost',
        7 => 'tests\\integration\\updateshouldreturn404whenpostnotfound',
        8 => 'tests\\integration\\destroyshoulddeletepost',
        9 => 'tests\\integration\\destroyshouldreturn404whenpostnotfound',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DTOs/DTOControllerIntegrationTest.php' => 
    array (
      0 => '734d6ede025c366bcfb7c5c7c3be59802795b687',
      1 => 
      array (
        0 => 'tests\\integration\\dtos\\dtocontrollerintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\dtos\\setup',
        1 => 'tests\\integration\\dtos\\testpostcontrollercreatewithvaliddto',
        2 => 'tests\\integration\\dtos\\testpostcontrollerupdatewithvaliddto',
        3 => 'tests\\integration\\dtos\\testcontrollerhandlesvalidationerrors',
        4 => 'tests\\integration\\dtos\\testdtodatasanitization',
        5 => 'tests\\integration\\dtos\\testdtotypeconversion',
        6 => 'tests\\integration\\dtos\\testdtoinbatchoperations',
        7 => 'tests\\integration\\dtos\\testdtomemoryefficiency',
        8 => 'tests\\integration\\dtos\\testdtoserialization',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DTOs/DTOValidationIntegrationTest.php' => 
    array (
      0 => '12eeaaddff5cfda964591f3f346b65fe021d83a2',
      1 => 
      array (
        0 => 'tests\\integration\\dtos\\dtovalidationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\dtos\\setup',
        1 => 'tests\\integration\\dtos\\testcreatepostdtovalidationintegration',
        2 => 'tests\\integration\\dtos\\testcreatepostdtotitletooshort',
        3 => 'tests\\integration\\dtos\\testcreatepostdtocontenttooshort',
        4 => 'tests\\integration\\dtos\\testupdatepostdtovalidationintegration',
        5 => 'tests\\integration\\dtos\\testcreateattachmentdtovalidationintegration',
        6 => 'tests\\integration\\dtos\\testregisteruserdtovalidationintegration',
        7 => 'tests\\integration\\dtos\\testcreateipruledtovalidationintegration',
        8 => 'tests\\integration\\dtos\\testvalidationerrormessagesinchinese',
        9 => 'tests\\integration\\dtos\\testmultiplefieldvalidationerrors',
        10 => 'tests\\integration\\dtos\\testdtojsonserialization',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DIValidationIntegrationTest.php' => 
    array (
      0 => 'dd04baca1324ddeab62f27030bc8860c9dfcafeb',
      1 => 
      array (
        0 => 'tests\\integration\\divalidationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\test_can_resolve_validator_interface_from_container',
        2 => 'tests\\integration\\test_can_resolve_validator_factory_from_container',
        3 => 'tests\\integration\\test_validator_from_factory_has_chinese_messages',
        4 => 'tests\\integration\\test_container_validator_has_custom_rules',
        5 => 'tests\\integration\\test_validator_factory_create_methods',
        6 => 'tests\\integration\\test_dto_validator_has_password_confirmation_rule',
        7 => 'tests\\integration\\test_container_validator_consistency',
        8 => 'tests\\integration\\test_validator_error_message_localization',
        9 => 'tests\\integration\\test_validator_performance_and_memory',
        10 => 'tests\\integration\\test_simplified_validation_scenarios',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_routing_system.php' => 
    array (
      0 => '15fe26cba36a0c3060148c755d50b99afbac3dbf',
      1 => 
      array (
        0 => 'mockserverrequest',
        1 => 'mockuri',
      ),
      2 => 
      array (
        0 => '__construct',
        1 => 'getmethod',
        2 => 'geturi',
        3 => '__construct',
        4 => 'getpath',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_route_cache.php' => 
    array (
      0 => '76fbaa467473d2285823a56bf914aca96f0127a3',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'createtestroutes',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_middleware_system.php' => 
    array (
      0 => 'dea390aee7446becc96d180b2c4425a0e81b273c',
      1 => 
      array (
        0 => 'loggingmiddleware',
        1 => 'authmiddleware',
      ),
      2 => 
      array (
        0 => 'getmethod',
        1 => 'geturi',
        2 => 'getpath',
        3 => 'withattribute',
        4 => 'getattribute',
        5 => 'getattributes',
        6 => 'getprotocolversion',
        7 => 'withprotocolversion',
        8 => 'getheaders',
        9 => 'hasheader',
        10 => 'getheader',
        11 => 'getheaderline',
        12 => 'withheader',
        13 => 'withaddedheader',
        14 => 'withoutheader',
        15 => 'getbody',
        16 => '__tostring',
        17 => 'withbody',
        18 => 'getrequesttarget',
        19 => 'withrequesttarget',
        20 => 'withmethod',
        21 => 'withuri',
        22 => 'getserverparams',
        23 => 'getcookieparams',
        24 => 'withcookieparams',
        25 => 'getqueryparams',
        26 => 'withqueryparams',
        27 => 'getuploadedfiles',
        28 => 'withuploadedfiles',
        29 => 'getparsedbody',
        30 => 'withparsedbody',
        31 => 'withoutattribute',
        32 => '__construct',
        33 => 'getbody',
        34 => '__construct',
        35 => '__tostring',
        36 => 'getstatuscode',
        37 => 'withstatus',
        38 => 'getreasonphrase',
        39 => 'getprotocolversion',
        40 => 'withprotocolversion',
        41 => 'getheaders',
        42 => 'hasheader',
        43 => 'getheader',
        44 => 'getheaderline',
        45 => 'withheader',
        46 => 'withaddedheader',
        47 => 'withoutheader',
        48 => 'withbody',
        49 => '__construct',
        50 => 'execute',
        51 => '__construct',
        52 => 'execute',
        53 => 'handle',
        54 => 'getbody',
        55 => '__tostring',
        56 => 'getstatuscode',
        57 => 'withstatus',
        58 => 'getreasonphrase',
        59 => 'getprotocolversion',
        60 => 'withprotocolversion',
        61 => 'getheaders',
        62 => 'hasheader',
        63 => 'getheader',
        64 => 'getheaderline',
        65 => 'withheader',
        66 => 'withaddedheader',
        67 => 'withoutheader',
        68 => 'withbody',
      ),
      3 => 
      array (
      ),
    ),
  ),
));