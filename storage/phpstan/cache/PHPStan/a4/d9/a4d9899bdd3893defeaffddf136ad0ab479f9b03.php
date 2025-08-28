<?php declare(strict_types = 1);

// odsl-/var/www/html/tests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/tests/UI/PostUITest.php' => 
    array (
      0 => 'd2bb56c4bbd32fee2b00292991b9c09db1c6c9ae',
      1 => 
      array (
        0 => 'tests\\ui\\postuitest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\testshoulddisplaypostlist',
        1 => 'tests\\ui\\testshouldcreatenewpost',
        2 => 'tests\\ui\\testshouldeditexistingpost',
        3 => 'tests\\ui\\testshoulddeletepost',
        4 => 'tests\\ui\\testshouldhandleresponsivelayout',
        5 => 'tests\\ui\\testshouldsupportdarkmode',
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
      0 => '44d5ff1a8620d4800f3ebbe1079064d68aacae43',
      1 => 
      array (
        0 => 'tests\\ui\\crossbrowsertest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\testbrowseraction',
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
      0 => '7c6dac566aef52d638054f5b384b01bf399e57f2',
      1 => 
      array (
        0 => 'tests\\ui\\userexperiencetest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\testshouldmeetaccessibilitystandards',
        1 => 'tests\\ui\\testshouldprovidegooduserinteraction',
        2 => 'tests\\ui\\testshouldperformwell',
        3 => 'tests\\ui\\testshouldhandleerrors',
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
      0 => 'd93ce77f736e225c567b688c0d9d9ce1cc2f6b14',
      1 => 
      array (
        0 => 'tests\\unit\\database\\databaseconnectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\database\\setup',
        1 => 'tests\\unit\\database\\testcreatessingletonpdoinstance',
        2 => 'tests\\unit\\database\\testexecutesquerysuccessfully',
        3 => 'tests\\unit\\database\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/PostRepositoryPerformanceTest.php' => 
    array (
      0 => '97b095efd4d8105d133840e5c91479f1f7bec657',
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
      0 => 'd7882f44fc2403a504e90b89e453c9dd7ec6ed60',
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
      0 => '71a530d54f65e1e4ae4477367632931c9b534e0b',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\userrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\setuptestdatabase',
        2 => 'tests\\unit\\repository\\testcreateusersuccessfully',
        3 => 'tests\\unit\\repository\\testupdateusersuccessfully',
        4 => 'tests\\unit\\repository\\testdeleteusersuccessfully',
        5 => 'tests\\unit\\repository\\testfinduserbyuuid',
        6 => 'tests\\unit\\repository\\testfinduserbyusername',
        7 => 'tests\\unit\\repository\\testfinduserbyemail',
        8 => 'tests\\unit\\repository\\testpreventduplicateusername',
        9 => 'tests\\unit\\repository\\testpreventduplicateemail',
        10 => 'tests\\unit\\repository\\testfinduserbyid',
        11 => 'tests\\unit\\repository\\testreturnnullwhenusernotfound',
        12 => 'tests\\unit\\repository\\testupdatelastlogintime',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/IpRepositoryTest.php' => 
    array (
      0 => '45196beca358356e3222eb6af64806d0879aac2e',
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
      0 => '67675a2fa2772457d1e07fb9900cbd985c2f0ba2',
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
      0 => '8c7f77678c47e1dee378120b87fd44128255c18d',
      1 => 
      array (
        0 => 'tests\\unit\\repositories\\attachmentrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repositories\\setup',
        1 => 'tests\\unit\\repositories\\createtesttables',
        2 => 'tests\\unit\\repositories\\testshouldcreateattachmentsuccessfully',
        3 => 'tests\\unit\\repositories\\testshouldfindattachmentbyid',
        4 => 'tests\\unit\\repositories\\testshouldfindattachmentbyuuid',
        5 => 'tests\\unit\\repositories\\testshouldreturnnullfornonexistentid',
        6 => 'tests\\unit\\repositories\\testshouldreturnnullfornonexistentuuid',
        7 => 'tests\\unit\\repositories\\testshouldgetattachmentsbypostid',
        8 => 'tests\\unit\\repositories\\testshouldsoftdeleteattachment',
        9 => 'tests\\unit\\repositories\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Models/PostTest.php' => 
    array (
      0 => 'd36b7b46106375e1d03b36e04165dca891bbcb58',
      1 => 
      array (
        0 => 'tests\\unit\\models\\posttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\testcorrectlyinitializeswithvaliddata',
        1 => 'tests\\unit\\models\\testhandlesnullablefieldscorrectly',
        2 => 'tests\\unit\\models\\testsetsdefaultvaluescorrectly',
        3 => 'tests\\unit\\models\\teststoresrawhtmlintitleandcontent',
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
      0 => '703d797d0c214eb7d5024ab1fd1496755979073a',
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
      0 => 'a44acd9b30c2985db2a0afcae8fff6fbcfd05c7e',
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
      0 => 'dfda63305336d9699cfef908453a3448ecac7df5',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\xssprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\testescapesbasichtml',
        2 => 'tests\\unit\\services\\security\\testescapeshtmlattributes',
        3 => 'tests\\unit\\services\\security\\testhandlesnullinput',
        4 => 'tests\\unit\\services\\security\\testcleansarrayofstrings',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/SessionSecurityServiceTest.php' => 
    array (
      0 => '0478364a7b2769b9fc9a28b349348a4266a22578',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\sessionsecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\testinitializessecuresessioninproduction',
        3 => 'tests\\unit\\services\\security\\testinitializessecuresessionindevelopment',
        4 => 'tests\\unit\\services\\security\\testsetsusersessionwithuseragentbinding',
        5 => 'tests\\unit\\services\\security\\testvalidatesuseragentcorrectly',
        6 => 'tests\\unit\\services\\security\\testvalidatessessionipcorrectly',
        7 => 'tests\\unit\\services\\security\\testperformscomprehensivesecuritycheck',
        8 => 'tests\\unit\\services\\security\\testdetectsuseragentchange',
        9 => 'tests\\unit\\services\\security\\testdetectsipchange',
        10 => 'tests\\unit\\services\\security\\testhandlesipverificationflow',
        11 => 'tests\\unit\\services\\security\\testdetectsexpiredsession',
        12 => 'tests\\unit\\services\\security\\testupdatesactivitytime',
        13 => 'tests\\unit\\services\\security\\testdestroyssessionsecurely',
        14 => 'tests\\unit\\services\\security\\testregeneratessessionid',
        15 => 'tests\\unit\\services\\security\\testhandlesmissingsessiondata',
        16 => 'tests\\unit\\services\\security\\testhandlesipverificationtimeout',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/LoggingSecurityServiceTest.php' => 
    array (
      0 => 'a8df81445c3d2c72d68d48d6178a9c59a145e017',
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
        4 => 'tests\\unit\\services\\security\\testsanitizescontextdatacorrectly',
        5 => 'tests\\unit\\services\\security\\testappliesrequestwhitelistcorrectly',
        6 => 'tests\\unit\\services\\security\\testtruncateslongstrings',
        7 => 'tests\\unit\\services\\security\\testenrichessecuritycontext',
        8 => 'tests\\unit\\services\\security\\testenrichesrequestcontext',
        9 => 'tests\\unit\\services\\security\\testlogssecurityeventscorrectly',
        10 => 'tests\\unit\\services\\security\\testlogsrequestswithwhitelist',
        11 => 'tests\\unit\\services\\security\\testhandlessensitivefieldvariations',
        12 => 'tests\\unit\\services\\security\\testhandlesemptyandnullvalues',
        13 => 'tests\\unit\\services\\security\\testreturnslogstatistics',
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
      0 => '80b5c99c8bc7caa74cb8496aee570915ee17cf60',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\csrfprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\testgeneratesvalidtoken',
        3 => 'tests\\unit\\services\\security\\testvalidatescorrecttoken',
        4 => 'tests\\unit\\services\\security\\testthrowsexceptionforemptytoken',
        5 => 'tests\\unit\\services\\security\\testthrowsexceptionforinvalidtoken',
        6 => 'tests\\unit\\services\\security\\testthrowsexceptionforexpiredtoken',
        7 => 'tests\\unit\\services\\security\\testupdatestokenaftersuccessfulvalidation',
        8 => 'tests\\unit\\services\\security\\testinitializestokenpool',
        9 => 'tests\\unit\\services\\security\\testsupportsmultiplevalidtokensinpool',
        10 => 'tests\\unit\\services\\security\\testvalidatestokenfrompoolwithconstanttimecomparison',
        11 => 'tests\\unit\\services\\security\\testremovestokenfrompoolafteruse',
        12 => 'tests\\unit\\services\\security\\testcleansexpiredtokensfrompool',
        13 => 'tests\\unit\\services\\security\\testlimitstokenpoolsize',
        14 => 'tests\\unit\\services\\security\\testgettokenpoolstatusreturnscorrectinfo',
        15 => 'tests\\unit\\services\\security\\testfallsbacktosingletokenmodewhenpoolnotinitialized',
        16 => 'tests\\unit\\services\\security\\testistokenvalidreturnsfalseforinvalidtoken',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/PostServiceTest.php' => 
    array (
      0 => '6b39dc7093321c96d6fdae00102cf1a4e8508e02',
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
      0 => '4cca3878bb193c019d41380eddaf6d9796a6189b',
      1 => 
      array (
        0 => 'tests\\unit\\services\\cacheservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\teststoreandretrievedata',
        2 => 'tests\\unit\\services\\testhandleconnectionfailure',
        3 => 'tests\\unit\\services\\testhandleconcurrentrequests',
        4 => 'tests\\unit\\services\\testclearcache',
        5 => 'tests\\unit\\services\\testdeletespecifickey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AuthServiceTest.php' => 
    array (
      0 => 'd8ced8922cb6c4cb9f09500a56f6ab964e502316',
      1 => 
      array (
        0 => 'tests\\unit\\services\\authservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\teardown',
        2 => 'tests\\unit\\services\\testit_should_register_new_user_successfully',
        3 => 'tests\\unit\\services\\testit_should_validate_registration_data',
        4 => 'tests\\unit\\services\\testit_should_login_user_successfully',
        5 => 'tests\\unit\\services\\testit_should_fail_login_with_invalid_credentials',
        6 => 'tests\\unit\\services\\testit_should_not_login_inactive_user',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AttachmentServiceTest.php' => 
    array (
      0 => 'e0e85089350cb3ccac54d61cdf6e9935bf9a58cd',
      1 => 
      array (
        0 => 'tests\\unit\\services\\attachmentservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testshoulduploadfilesuccessfully',
        2 => 'tests\\unit\\services\\testshouldrejectinvalidfiletype',
        3 => 'tests\\unit\\services\\testshouldrejectoversizedfile',
        4 => 'tests\\unit\\services\\testshouldrejectuploadtononexistentpost',
        5 => 'tests\\unit\\services\\createuploadedfilemock',
        6 => 'tests\\unit\\services\\teardown',
        7 => 'tests\\unit\\services\\recursiveremovedirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/IpServiceTest.php' => 
    array (
      0 => 'aa0ddd6a41cad571c64a8728864f6328b26057f4',
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
      0 => '06c392ee893ca0e3f59b17f44e02f3e743f9907f',
      1 => 
      array (
        0 => 'tests\\unit\\services\\ratelimitservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testshouldallowfirstrequest',
        2 => 'tests\\unit\\services\\testshouldrejectwhenlimitexceeded',
        3 => 'tests\\unit\\services\\testshouldhandlecachefailuregracefully',
        4 => 'tests\\unit\\services\\testshouldincrementrequestcount',
        5 => 'tests\\unit\\services\\testshouldhandlesetfailure',
        6 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/CsrfProtectionTest.php' => 
    array (
      0 => '01ebd2b23543f7ea749fa8b1ae5b22dda893da02',
      1 => 
      array (
        0 => 'tests\\security\\csrfprotectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\testshouldrejectrequestwithoutcsrftoken',
        2 => 'tests\\security\\testshouldrejectrequestwithinvalidcsrftoken',
        3 => 'tests\\security\\testshouldacceptrequestwithvalidcsrftoken',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/SqlInjectionTest.php' => 
    array (
      0 => '7db235e24d9c8f56111fd8297380b9ef81240008',
      1 => 
      array (
        0 => 'tests\\security\\sqlinjectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\testshouldpreventsqlinjectionintitlesearch',
        3 => 'tests\\security\\testshouldhandlespecialcharactersincontent',
        4 => 'tests\\security\\testshouldpreventsqlinjectioninuseridfilter',
        5 => 'tests\\security\\testshouldsanitizesearchinput',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/XssPreventionTest.php' => 
    array (
      0 => '71c6a6c2d12374218a7fc5f3c2e7d4506c381b36',
      1 => 
      array (
        0 => 'tests\\security\\xsspreventiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\testshouldescapehtmlinposttitle',
        2 => 'tests\\security\\testshouldescapehtmlinpostcontent',
        3 => 'tests\\security\\testshouldhandleencodedxssattempts',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/FileUploadSecurityTest.php' => 
    array (
      0 => 'db77950348d618c530088740a3f5e8de3dfbdb64',
      1 => 
      array (
        0 => 'tests\\security\\fileuploadsecuritytest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\testshouldrejectexecutablefiles',
        2 => 'tests\\security\\testshouldrejectdoubleextensionfiles',
        3 => 'tests\\security\\testshouldrejectoversizedfiles',
        4 => 'tests\\security\\testshouldrejectmaliciousmimetypes',
        5 => 'tests\\security\\testshouldpreventpathtraversal',
        6 => 'tests\\security\\testshouldacceptvalidfiles',
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
      0 => '5f87f7236d3bb7f61984f6674650b8befbfd35cd',
      1 => 
      array (
        0 => 'tests\\security\\passwordhashingtest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\testshouldhashpasswordusingargon2id',
        3 => 'tests\\security\\testshoulduseappropriatehashingoptions',
        4 => 'tests\\security\\testshouldrejectweakpasswords',
        5 => 'tests\\security\\testshouldpreventpasswordreuse',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/RateLimitTest.php' => 
    array (
      0 => '5734ee0c15fc2c4d3a6861dd142363b75d4a49e1',
      1 => 
      array (
        0 => 'tests\\integration\\ratelimittest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\testshouldlimitratesuccessfully',
        2 => 'tests\\integration\\testshouldresetlimitaftertimewindow',
        3 => 'tests\\integration\\testshouldhandledifferentipsindependently',
        4 => 'tests\\integration\\testshouldhandleserviceunavailability',
        5 => 'tests\\integration\\testshouldincrementcountercorrectly',
        6 => 'tests\\integration\\testshouldhandlemaxattemptsreached',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AuthControllerTest.php' => 
    array (
      0 => 'd7ea662bb819e08a2d5d01a1d17f07bda5434d1e',
      1 => 
      array (
        0 => 'tests\\integration\\authcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\testregisterusersuccessfully',
        3 => 'tests\\integration\\testreturnvalidationerrorsforinvalidregistrationdata',
        4 => 'tests\\integration\\testloginusersuccessfully',
        5 => 'tests\\integration\\testreturnerrorforinvalidlogin',
        6 => 'tests\\integration\\testlogoutusersuccessfully',
        7 => 'tests\\integration\\testgetuserinfosuccessfully',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Repositories/PostRepositoryTest.php' => 
    array (
      0 => '3e557b1e96a50ec1884738805048458c15240425',
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
      0 => '55cd2f6660ce3a88f2bbafe3e5a0b5088fb0da47',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\testuploadshouldstorefilesuccessfully',
        2 => 'tests\\integration\\testuploadshouldreturn400forinvalidfile',
        3 => 'tests\\integration\\testlistshouldreturnattachments',
        4 => 'tests\\integration\\testdeleteshouldremoveattachment',
        5 => 'tests\\integration\\testdeleteshouldreturn404fornonexistentattachment',
        6 => 'tests\\integration\\testdeleteshouldreturn400forinvaliduuid',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/PostControllerTest.php' => 
    array (
      0 => '825ecd3f8362f738835c9c1132507b3ab21f4c0e',
      1 => 
      array (
        0 => 'tests\\integration\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\testindexshouldreturnpaginatedposts',
        2 => 'tests\\integration\\testshowshouldreturnpostdetails',
        3 => 'tests\\integration\\teststoreshouldcreatenewpost',
        4 => 'tests\\integration\\teststoreshouldreturn400whenvalidationfails',
        5 => 'tests\\integration\\testupdateshouldmodifyexistingpost',
        6 => 'tests\\integration\\testupdateshouldreturn404whenpostnotfound',
        7 => 'tests\\integration\\testdestroyshoulddeletepost',
        8 => 'tests\\integration\\testupdatepinstatusshouldupdatepinstatus',
        9 => 'tests\\integration\\testupdatepinstatusshouldreturn422wheninvalidstatetransition',
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
      0 => '313500b5ae5c6612ecf871852b902b8f8c91ea07',
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
      0 => 'bbf8b545afd880d5d62f5052d3e1236c9cb4ac03',
      1 => 
      array (
        0 => 'tests\\integration\\filesystembackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtestfiles',
        2 => 'tests\\integration\\testbackupfilessuccessfully',
        3 => 'tests\\integration\\testrestorefilessuccessfully',
        4 => 'tests\\integration\\testhandlebackuperrorsgracefully',
        5 => 'tests\\integration\\testhandlerestoreerrorsgracefully',
        6 => 'tests\\integration\\testhandlepermissionerrors',
        7 => 'tests\\integration\\testmaintainfilemetadataduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DatabaseBackupTest.php' => 
    array (
      0 => 'e76c1bc0e62adbc786f30dcdca25ca1799eec4e2',
      1 => 
      array (
        0 => 'tests\\integration\\databasebackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtesttables',
        2 => 'tests\\integration\\inserttestdata',
        3 => 'tests\\integration\\testbackupdatabasesuccessfully',
        4 => 'tests\\integration\\testrestoredatabasesuccessfully',
        5 => 'tests\\integration\\testhandlebackuperrorsgracefully',
        6 => 'tests\\integration\\testhandlerestoreerrorsgracefully',
        7 => 'tests\\integration\\testmaintaindataintegrityduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AttachmentUploadTest.php' => 
    array (
      0 => 'ebee3d313ef901738947ddae938feb607830ad23',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentuploadtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createuploadedfilemock',
        2 => 'tests\\integration\\testshould_handle_concurrent_uploads',
        3 => 'tests\\integration\\testshould_handle_large_file_upload',
        4 => 'tests\\integration\\testshould_validate_file_types',
        5 => 'tests\\integration\\testshould_handle_disk_full_error',
        6 => 'tests\\integration\\testshould_handle_permission_error',
        7 => 'tests\\integration\\teardown',
        8 => 'tests\\integration\\createtesttables',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/TestCase.php' => 
    array (
      0 => 'd9625aabbf7dc1cdc9de48347877174e9da73d6e',
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
      0 => 'f5b68547762defd63335e83cd592d5505d6235c3',
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
      0 => 'f63fbbc03b8526c4340b0a7a3a0a6a3e0d1aad6e',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\basedtotest',
        1 => 'tests\\unit\\dtos\\testablebasedto',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\setup',
        1 => 'tests\\unit\\dtos\\teardown',
        2 => 'tests\\unit\\dtos\\createtestdto',
        3 => 'tests\\unit\\dtos\\testconstructoracceptsvalidator',
        4 => 'tests\\unit\\dtos\\testtoarrayisimplemented',
        5 => 'tests\\unit\\dtos\\testjsonserializeusestoarray',
        6 => 'tests\\unit\\dtos\\testvalidatecallsvalidatorwithcorrectdata',
        7 => 'tests\\unit\\dtos\\testvalidatethrowsexceptiononvalidationfailure',
        8 => 'tests\\unit\\dtos\\testgetstringreturnscorrectvalue',
        9 => 'tests\\unit\\dtos\\testgetintreturnscorrectvalue',
        10 => 'tests\\unit\\dtos\\testgetboolreturnscorrectvalue',
        11 => 'tests\\unit\\dtos\\testgetvaluereturnscorrectvalue',
        12 => 'tests\\unit\\dtos\\testvalidationrulesareabstract',
        13 => 'tests\\unit\\dtos\\getvalidationrules',
        14 => 'tests\\unit\\dtos\\toarray',
        15 => 'tests\\unit\\dtos\\testvalidate',
        16 => 'tests\\unit\\dtos\\testgetstring',
        17 => 'tests\\unit\\dtos\\testgetint',
        18 => 'tests\\unit\\dtos\\testgetbool',
        19 => 'tests\\unit\\dtos\\testgetvalue',
        20 => 'tests\\unit\\dtos\\testgetvalidationrules',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/DTOValidationTest.php' => 
    array (
      0 => '27c41f98b0a8790b06929b93c580296a747b9bea',
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
      0 => 'a1dcb57fe7fb74d8f398f16dcf3b761a09241822',
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
      0 => '71d5b9ff4a2e5377c6a5a4e7ac8a8a6610c6fa11',
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
      0 => '29930d46c3d2203e913d063625aae44f87b0ece4',
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
      0 => '9b88870e37f50b052287318a89ed80ea1187f7fa',
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
      0 => '7e7d75b3d72a049d4b5188b844fa58d50c596699',
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
      0 => '85ebbd57fbd129f6a24b781870039098f1b39905',
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
    '/var/www/html/tests/Unit/Services/Security/FileSecurityServiceTest.php' => 
    array (
      0 => 'dfe04f7b9ab0989984012e6d3fc999a3d6d48db2',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\filesecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\testvalidateuploadwithvalidfile',
        2 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithemptyfile',
        3 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithoversizedfile',
        4 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithinvalidmimetype',
        5 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithmismatchedextensionandmimetype',
        6 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithpathtraversalinfilename',
        7 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithnullbyteinfilename',
        8 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithforbiddenextension',
        9 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithmaliciouscontent',
        10 => 'tests\\unit\\services\\security\\testgeneratesecurefilename',
        11 => 'tests\\unit\\services\\security\\testgeneratesecurefilenamewithprefix',
        12 => 'tests\\unit\\services\\security\\testsanitizefilename',
        13 => 'tests\\unit\\services\\security\\testisinalloweddirectorywithvalidpath',
        14 => 'tests\\unit\\services\\security\\testisinalloweddirectorywithinvalidpath',
        15 => 'tests\\unit\\services\\security\\testdetectactualmimetypewithnonexistentfile',
        16 => 'tests\\unit\\services\\security\\testdetectactualmimetypewithvalidfile',
        17 => 'tests\\unit\\services\\security\\createuploadedfile',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/InvalidTokenExceptionTest.php' => 
    array (
      0 => 'd5f068e36635e100592f44f28b45a5979e729c4d',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\invalidtokenexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testmalformedfactorymethod',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testsignatureinvalidfactorymethod',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testalgorithmmismatchfactorymethod',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testissuerinvalidfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testaudienceinvalidfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testsubjectmissingfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testclaimsinvalidfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testblacklistedfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testnotbeforefactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/AuthenticationExceptionTest.php' => 
    array (
      0 => 'd6961308354a167955a449d76feb584cf2cabe84',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\authenticationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testattemptid',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetters',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterswithemptycontext',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testretryability',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testrequiresaccountaction',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testinvalidcredentialsfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountlockedfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountdisabledfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountnotverifiedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testtoomanyattemptsfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testusernotfoundfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testpasswordexpiredfactorymethod',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testmissingcredentialsfactorymethod',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testinvalidtokenfactorymethod',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testtokenrequiredfactorymethod',
        23 => 'tests\\unit\\domains\\auth\\exceptions\\testinsufficientprivilegesfactorymethod',
        24 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        25 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        26 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/TokenExpiredExceptionTest.php' => 
    array (
      0 => '829d27d0d876e5931952b2acce70ae67d58887b2',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\tokenexpiredexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testaccesstokenconstruction',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testrefreshtokenconstruction',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testwithoutexpiredtime',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatseconds',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsinglesecond',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatminutes',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsingleminute',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformathours',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsinglehour',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatdays',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsingleday',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessageaccesstoken',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessagerefreshtoken',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextinformation',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testusingcurrenttime',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testaccesstokenfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testrefreshtokenfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testfactorymethodswithdefaults',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testzerodurationedgecase',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testnegativeduration',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/RefreshTokenExceptionTest.php' => 
    array (
      0 => '35a399cbff13a6672043bb866d38fbae1f0149be',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\refreshtokenexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testoperationid',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetters',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterswithemptycontext',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testnotfoundfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testrevokedfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testalreadyusedfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testdevicemismatchfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testusermismatchfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\teststoragefailedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testdeletionfailedfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testrotationfailedfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testlimitexceededfactorymethod',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testfamilymismatchfactorymethod',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        23 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/JwtExceptionTest.php' => 
    array (
      0 => '36efb6a87b98826236fde23b7d234b8657d3acef',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\jwtexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\createconcretejwtexception',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\__construct',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithdefaults',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterandsetter',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testaddcontext',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testerrortype',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testgeterrordetails',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testgetuserfriendlymessage',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testtoarray',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testtostringwithoutcontext',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testtostringwithcontext',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexcontextdata',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testinheritancechain',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextimmutability',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/TokenGenerationExceptionTest.php' => 
    array (
      0 => '2bed3b19e6e0223e20a906b491231dac7331ec14',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\tokengenerationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testgenerationattemptid',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testkeyinvalidfactorymethod',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testkeymissingfactorymethod',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testpayloadinvalidfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testalgorithmunsupportedfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testclaimsinvalidfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testsignaturefailedfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testresourceexhaustedfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testencodingfailedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testfactorymethodswithdefaults',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/TokenPairTest.php' => 
    array (
      0 => 'f3152bb6edf3106d6bcc6c074adfe4a87e0d6256',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\tokenpairtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithcustomtokentype',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetaccesstokenexpiresin',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetrefreshtokenexpiresin',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokenexpired',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testisrefreshtokenexpired',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testisfullyexpired',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testcanrefresh',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpiry',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpirywithcustomthreshold',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetauthorizationheader',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetauthorizationheaderwithcustomtokentype',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoapiresponse',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoapiresponsewithoutrefreshtoken',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyaccesstoken',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaccesstokenformat',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaccesstokenparts',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyrefreshtoken',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtooshortrefreshtoken',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongrefreshtoken',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptytokentype',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidtokentype',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithaccesstokenexpirationinpast',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithrefreshtokenexpirationinpast',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithrefreshtokenexpirationbeforeaccesstoken',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithexcessivetimeinterval',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpirywithnegativethreshold',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/DeviceInfoTest.php' => 
    array (
      0 => '1a9ae58433c2ce0e986138a126148b3ab8e3ae1a',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\deviceinfotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithminimaldata',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithwindowschrome',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithmacsafari',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithandroidmobile',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithipad',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithfirefox',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithcustomdevicename',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarray',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetdevicetype',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfingerprint',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullbrowserinfo',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullbrowserinfowithoutbrowser',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullplatforminfo',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullplatforminfowithoutplatform',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testmatches',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtosummary',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testmaskipaddressipv4',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testmaskipaddressipv6',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydeviceid',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdeviceid',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvaliddeviceidcharacters',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydevicename',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdevicename',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyuseragent',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolonguseragent',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyipaddress',
        32 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidipaddress',
        33 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidplatform',
        34 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidbrowser',
        35 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithmultipledevicetypestrue',
        36 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnodevicetypetrue',
        37 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/JwtPayloadTest.php' => 
    array (
      0 => '518750a38d480ac4ab0a918016e04bbeb116c538',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\jwtpayloadtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnotbeforeandcustomclaims',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithvaliddata',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithstringaudience',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithnotbefore',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testisexpired',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactive',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactivewithoutnotbefore',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testhasaudience',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarraywithsingleaudience',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyjti',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongjti',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptysubject',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidsubject',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnonnumericsubject',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyissuer',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyaudience',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaudiencevalues',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithexpirationbeforeissuedtime',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnotbeforeafterexpiration',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithreservedcustomclaim',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/TokenBlacklistEntryTest.php' => 
    array (
      0 => '543b36c159f873b95f1fb692a2a1b9aa9d8e353d',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\tokenblacklistentrytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithminimaldata',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarray',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithstringdates',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testforuserlogout',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testforsecuritybreach',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testforsecuritybreachwithinvalidreason',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testforaccountchange',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testcanbecleanedup',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testissecurityrelated',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testisuserinitiated',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testissysteminitiated',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetreasondescription',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetpriority',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactive',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testtodatabasearray',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtodatabasearraywithemptymetadata',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetvalidtokentypes',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetvalidreasons',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyjti',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongjti',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidtokentype',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidreason',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithblacklistedtimetooold',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithblacklistedtimetoofuture',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvaliduserid',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydeviceid',
        32 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdeviceid',
        33 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnonserializablemetadata',
        34 => 'tests\\unit\\domains\\auth\\valueobjects\\testforaccountchangewithinvalidchangetype',
        35 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Shared/Config/JwtConfigTest.php' => 
    array (
      0 => 'd4b666cf4a046187729b071e804f90f2d12eddc2',
      1 => 
      array (
        0 => 'tests\\unit\\shared\\config\\jwtconfigtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\shared\\config\\setup',
        1 => 'tests\\unit\\shared\\config\\teardown',
        2 => 'tests\\unit\\shared\\config\\testsuccessfulconfigurationload',
        3 => 'tests\\unit\\shared\\config\\testprivatekeymissing',
        4 => 'tests\\unit\\shared\\config\\testpublickeymissing',
        5 => 'tests\\unit\\shared\\config\\testinvalidprivatekeyformat',
        6 => 'tests\\unit\\shared\\config\\testinvalidpublickeyformat',
        7 => 'tests\\unit\\shared\\config\\testunsupportedalgorithm',
        8 => 'tests\\unit\\shared\\config\\testinvalidaccesstokenttl',
        9 => 'tests\\unit\\shared\\config\\testinvalidrefreshtokenttl',
        10 => 'tests\\unit\\shared\\config\\testrefreshtokenttllessthanaccesstoken',
        11 => 'tests\\unit\\shared\\config\\testdefaultvalues',
        12 => 'tests\\unit\\shared\\config\\testgetbasepayload',
        13 => 'tests\\unit\\shared\\config\\testgetconfigsummary',
        14 => 'tests\\unit\\shared\\config\\testexpirytimestamps',
        15 => 'tests\\unit\\shared\\config\\setvalidenvironmentvariables',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/JwtTokenServiceInterfaceTest.php' => 
    array (
      0 => '0116047e36f7e576ceabd36ee82ce6ac49e290aa',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\jwttokenserviceinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testgeneratetokenpairmethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testvalidateaccesstokenmethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testvalidaterefreshtokenmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testextractpayloadmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testrefreshtokensmethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testrevoketokenmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallusertokensmethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testistokenrevokedmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testgettokenremainingtimemethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testistokennearexpirymethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testistokenownedbymethodsignature',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testistokenfromdevicemethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testgetalgorithmmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testgetaccesstokenttlmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testgetrefreshtokenttlmethodsignature',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/RefreshTokenRepositoryInterfaceTest.php' => 
    array (
      0 => 'b28a3f89ea34936b748839400b9d80a5f0837db9',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\refreshtokenrepositoryinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testcreatemethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyjtimethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testfindbytokenhashmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridanddevicemethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testupdatelastusedmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testrevokemethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallbyuseridmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallbydevicemethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testdeletemethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testvalidationmethods',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupmethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testcleanuprevokedmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testgetusertokenstatsmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testtokenfamilymethods',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testbatchmethods',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testgettokensnearexpirymethodsignature',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testgetsystemstatsmethodsignature',
        20 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        21 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        22 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/TokenBlacklistRepositoryInterfaceTest.php' => 
    array (
      0 => 'e1f26b6797ac997cebadd129b8d3649153676f39',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\tokenblacklistrepositoryinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testaddtoblacklistmethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testisblacklistedmethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testistokenhashblacklistedmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testremovefromblacklistmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyjtimethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testfindbydeviceidmethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyreasonmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testbatchaddtoblacklistmethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testbatchisblacklistedmethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testbatchremovefromblacklistmethodsignature',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testblacklistallusertokensmethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testblacklistalldevicetokensmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupexpiredentriesmethodsignature',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupoldentriesmethodsignature',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testgetblackliststatsmethodsignature',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testgetuserblackliststatsmethodsignature',
        20 => 'tests\\unit\\domains\\auth\\contracts\\testgetrecentblacklistentriesmethodsignature',
        21 => 'tests\\unit\\domains\\auth\\contracts\\testgethighpriorityentriesmethodsignature',
        22 => 'tests\\unit\\domains\\auth\\contracts\\testsearchmethodsignature',
        23 => 'tests\\unit\\domains\\auth\\contracts\\testcountsearchmethodsignature',
        24 => 'tests\\unit\\domains\\auth\\contracts\\testissizeexceededmethodsignature',
        25 => 'tests\\unit\\domains\\auth\\contracts\\testgetsizeinfomethodsignature',
        26 => 'tests\\unit\\domains\\auth\\contracts\\testoptimizemethodsignature',
        27 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        28 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        29 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
        30 => 'tests\\unit\\domains\\auth\\contracts\\testmethodsarepublic',
        31 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceextendsnootherinterface',
        32 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehasnoconstants',
        33 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehasnoproperties',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Jwt/FirebaseJwtProviderTest.php' => 
    array (
      0 => '2d6d962ce05081b9d248fef79d9ae5a3817ce6f3',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\jwt\\firebasejwtprovidertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\jwt\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\jwt\\teardown',
        2 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorsuccessfullyinitializesprovider',
        3 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionforinvalidprivatekey',
        4 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionforinvalidpublickey',
        5 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionformismatchedkeys',
        6 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgenerateaccesstokensuccessfully',
        7 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneraterefreshtokensuccessfully',
        8 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgenerateaccesstokenwithcustomttl',
        9 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneraterefreshtokenwithcustomttl',
        10 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidateaccesstokensuccessfully',
        11 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidaterefreshtokensuccessfully',
        12 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforemptytoken',
        13 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionformalformedtoken',
        14 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforexpiredtoken',
        15 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforwrongtokentype',
        16 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidsignature',
        17 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafesuccessfully',
        18 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafethrowsexceptionforemptytoken',
        19 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafethrowsexceptionforinvalidformat',
        20 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgettokenexpirationsuccessfully',
        21 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgettokenexpirationreturnsnullforinvalidtoken',
        22 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredforvalidtoken',
        23 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredforexpiredtoken',
        24 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredreturnsfalseforinvalidtoken',
        25 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidissuer',
        26 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidaudience',
        27 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneratedtokenshaveuniquejti',
        28 => 'tests\\unit\\infrastructure\\auth\\jwt\\generatetestkeys',
        29 => 'tests\\unit\\infrastructure\\auth\\jwt\\generatealternativekeys',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/JwtTokenServiceSimpleTest.php' => 
    array (
      0 => '19e7ecc6f25838332b5e00eb615330bcdb48a856',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\jwttokenservicesimpletest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_return_token_pair_when_valid_input',
        2 => 'tests\\unit\\domains\\auth\\services\\test_revokeallusertokens_should_delete_all_user_refresh_tokens',
        3 => 'tests\\unit\\domains\\auth\\services\\test_getalgorithm_should_return_rs256',
        4 => 'tests\\unit\\domains\\auth\\services\\test_getaccesstokenttl_should_return_config_value',
        5 => 'tests\\unit\\domains\\auth\\services\\test_getrefreshtokenttl_should_return_config_value',
        6 => 'tests\\unit\\domains\\auth\\services\\test_extractpayload_should_return_payload_without_validation',
        7 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_true_when_owned_by_user',
        8 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_false_when_not_owned_by_user',
        9 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_true_when_from_device',
        10 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_false_when_from_different_device',
        11 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_remaining_seconds',
        12 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_token_invalid',
        13 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_true_when_near_expiry',
        14 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_false_when_not_near_expiry',
        15 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_token_invalid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/JwtTokenServiceTest.php' => 
    array (
      0 => 'f4a3e35c3912ed49be8bf33f2490a19231f02b5a',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\jwttokenservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_return_token_pair_when_valid_input',
        2 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_throw_exception_when_jwt_provider_fails',
        3 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_throw_exception_when_repository_fails',
        4 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_return_payload_when_valid_token',
        5 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_throw_exception_when_token_blacklisted',
        6 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_skip_blacklist_check_when_disabled',
        7 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_return_payload_when_valid_token',
        8 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_throw_exception_when_not_found_in_database',
        9 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_throw_exception_when_revoked',
        10 => 'tests\\unit\\domains\\auth\\services\\test_extractpayload_should_return_payload_without_validation',
        11 => 'tests\\unit\\domains\\auth\\services\\test_refreshtokens_should_return_new_token_pair',
        12 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_add_token_to_blacklist',
        13 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_delete_refresh_token_from_repository',
        14 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_return_false_when_exception_occurs',
        15 => 'tests\\unit\\domains\\auth\\services\\test_revokeallusertokens_should_delete_all_user_refresh_tokens',
        16 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_blacklisted',
        17 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_false_when_not_blacklisted',
        18 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_token_invalid',
        19 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_remaining_seconds',
        20 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_expired',
        21 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_token_invalid',
        22 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_true_when_near_expiry',
        23 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_false_when_not_near_expiry',
        24 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_true_when_owned_by_user',
        25 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_false_when_not_owned_by_user',
        26 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_true_when_from_device',
        27 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_false_when_from_different_device',
        28 => 'tests\\unit\\domains\\auth\\services\\test_getalgorithm_should_return_rs256',
        29 => 'tests\\unit\\domains\\auth\\services\\test_getaccesstokenttl_should_return_config_value',
        30 => 'tests\\unit\\domains\\auth\\services\\test_getrefreshtokenttl_should_return_config_value',
        31 => 'tests\\unit\\domains\\auth\\services\\test_createjwtpayloadfromarray_should_create_valid_payload',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Entities/RefreshTokenTest.php' => 
    array (
      0 => 'ce8f4da151f869d4b35f8a3d9e09fc74ad63d914',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\entities\\refreshtokentest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\entities\\setup',
        1 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_create_valid_refresh_token',
        2 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_create_revoked_token',
        3 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_empty',
        4 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_too_short',
        5 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_contains_invalid_characters',
        6 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_user_id_invalid',
        7 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_token_hash_invalid',
        8 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_status_invalid',
        9 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_revoked_without_reason',
        10 => 'tests\\unit\\domains\\auth\\entities\\test_isexpired_should_return_true_when_token_expired',
        11 => 'tests\\unit\\domains\\auth\\entities\\test_isexpired_should_return_false_when_token_not_expired',
        12 => 'tests\\unit\\domains\\auth\\entities\\test_isrevoked_should_return_true_when_token_revoked',
        13 => 'tests\\unit\\domains\\auth\\entities\\test_isrevoked_should_return_false_when_token_active',
        14 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_true_when_token_active_and_not_expired',
        15 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_false_when_token_expired',
        16 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_false_when_token_revoked',
        17 => 'tests\\unit\\domains\\auth\\entities\\test_canberefreshed_should_return_true_when_token_valid_and_active',
        18 => 'tests\\unit\\domains\\auth\\entities\\test_canberefreshed_should_return_false_when_token_used',
        19 => 'tests\\unit\\domains\\auth\\entities\\test_markasrevoked_should_return_new_revoked_token',
        20 => 'tests\\unit\\domains\\auth\\entities\\test_markasrevoked_should_return_same_token_when_already_revoked',
        21 => 'tests\\unit\\domains\\auth\\entities\\test_markasused_should_return_new_used_token',
        22 => 'tests\\unit\\domains\\auth\\entities\\test_updatelastused_should_return_new_token_with_updated_time',
        23 => 'tests\\unit\\domains\\auth\\entities\\test_equals_should_return_true_when_same_jti',
        24 => 'tests\\unit\\domains\\auth\\entities\\test_equals_should_return_false_when_different_jti',
        25 => 'tests\\unit\\domains\\auth\\entities\\test_belongstouser_should_return_true_when_same_user',
        26 => 'tests\\unit\\domains\\auth\\entities\\test_belongstouser_should_return_false_when_different_user',
        27 => 'tests\\unit\\domains\\auth\\entities\\test_belongstodevice_should_return_true_when_same_device',
        28 => 'tests\\unit\\domains\\auth\\entities\\test_belongstodevice_should_return_false_when_different_device',
        29 => 'tests\\unit\\domains\\auth\\entities\\test_getremainingtime_should_return_correct_seconds',
        30 => 'tests\\unit\\domains\\auth\\entities\\test_getremainingtime_should_return_zero_when_expired',
        31 => 'tests\\unit\\domains\\auth\\entities\\test_isnearexpiry_should_return_true_when_near_expiry',
        32 => 'tests\\unit\\domains\\auth\\entities\\test_isnearexpiry_should_return_false_when_not_near_expiry',
        33 => 'tests\\unit\\domains\\auth\\entities\\test_jsonserialize_should_return_expected_array',
        34 => 'tests\\unit\\domains\\auth\\entities\\test_toarray_should_return_complete_array_with_sensitive_data',
        35 => 'tests\\unit\\domains\\auth\\entities\\test_tostring_should_return_expected_format',
        36 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_accept_minimum_valid_jti_length',
        37 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_accept_maximum_valid_jti_length',
        38 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_reject_jti_exceeding_maximum_length',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Repositories/RefreshTokenRepositoryTest.php' => 
    array (
      0 => '83999ba516e91e52d3f9898b2bd8d390bbfbe936',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\refreshtokenrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldreturntrue_whentokencreatedsuccessfully',
        2 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldthrowexception_whendatabasefails',
        3 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldhandleparenttokenjti_whenprovided',
        4 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldreturnarray_whentokenexists',
        5 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldreturnnull_whentokennotfound',
        6 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldthrowexception_whendatabasefails',
        7 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldreturnarray_whentokenexists',
        8 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldreturnnull_whentokennotfound',
        9 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldreturnarray_whentokensexist',
        10 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldincludeexpiredtokens_whenrequested',
        11 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldexcludeexpiredtokens_bydefault',
        12 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuseridanddevice_shouldreturnarray_whentokensexist',
        13 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldreturntrue_whenupdatesuccessful',
        14 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldusecurrenttime_whenlastusedatisnull',
        15 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldreturntrue_whenrevocationsuccessful',
        16 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldusedefaultreason_whenreasonnotprovided',
        17 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldreturnrevokedcount_whensuccessful',
        18 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldexcludejti_whenprovided',
        19 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbydevice_shouldreturnrevokedcount_whensuccessful',
        20 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldreturntrue_whendeletionsuccessful',
        21 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldreturnfalse_whentokennotfound',
        22 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldreturntrue_whentokenisrevoked',
        23 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldreturnfalse_whentokenisactive',
        24 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturntrue_whentokenisexpired',
        25 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturnfalse_whentokenisnotexpired',
        26 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturntrue_whentokennotfound',
        27 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisvalid_shouldreturntrue_whentokenisvalidandnotrevoked',
        28 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldreturncleanedcount_whensuccessful',
        29 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldusecurrenttime_whenbeforedateisnull',
        30 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanuprevoked_shouldreturncleanedcount_whensuccessful',
        31 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetusertokenstats_shouldreturnstatsarray_whensuccessful',
        32 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsystemstats_shouldreturnsystemstatsarray_whensuccessful',
        33 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchcreate_shouldreturncreatedcount_whensuccessful',
        34 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchcreate_shouldrollbackandthrowexception_whenfails',
        35 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchrevoke_shouldreturnrevokedcount_whensuccessful',
        36 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchrevoke_shouldreturnzero_whenjtisarrayisempty',
        37 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgettokensnearexpiry_shouldreturnarray_whentokensfound',
        38 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldthrowexception_whendatabasefails',
        39 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldthrowexception_whendatabasefails',
        40 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldthrowexception_whendatabasefails',
        41 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldthrowexception_whendatabasefails',
        42 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbydevice_shouldthrowexception_whendatabasefails',
        43 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldthrowexception_whendatabasefails',
        44 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldthrowexception_whendatabasefails',
        45 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldthrowexception_whendatabasefails',
        46 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldthrowexception_whendatabasefails',
        47 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanuprevoked_shouldthrowexception_whendatabasefails',
        48 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetusertokenstats_shouldthrowexception_whendatabasefails',
        49 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsystemstats_shouldthrowexception_whendatabasefails',
        50 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgettokensnearexpiry_shouldthrowexception_whendatabasefails',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php' => 
    array (
      0 => '8e048ee70e77f543594510508ef3e551fdada2e3',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\authenticationservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\testlogin_成功登入_應該返回登入回應',
        2 => 'tests\\unit\\domains\\auth\\services\\testlogin_使用者不存在_應該拋出認證例外',
        3 => 'tests\\unit\\domains\\auth\\services\\testlogin_使用者已被軟刪除_應該拋出認證例外',
        4 => 'tests\\unit\\domains\\auth\\services\\testlogin_超過最大token數量限制_應該撤銷最舊的token',
        5 => 'tests\\unit\\domains\\auth\\services\\testrefresh_成功重新整理_應該返回新的token對',
        6 => 'tests\\unit\\domains\\auth\\services\\testrefresh_無效的refreshtoken_應該拋出認證例外',
        7 => 'tests\\unit\\domains\\auth\\services\\testrefresh_過期的refreshtoken_應該拋出認證例外',
        8 => 'tests\\unit\\domains\\auth\\services\\testlogout_單一token登出_應該成功撤銷token',
        9 => 'tests\\unit\\domains\\auth\\services\\testlogout_全部token登出_應該撤銷使用者所有token',
        10 => 'tests\\unit\\domains\\auth\\services\\testlogout_沒有refreshtoken_應該仍然成功',
        11 => 'tests\\unit\\domains\\auth\\services\\testvalidateaccesstoken_有效的token_應該返回true',
        12 => 'tests\\unit\\domains\\auth\\services\\testvalidateaccesstoken_無效的token_應該返回false',
        13 => 'tests\\unit\\domains\\auth\\services\\testvalidaterefreshtoken_有效的token_應該返回true',
        14 => 'tests\\unit\\domains\\auth\\services\\testvalidaterefreshtoken_無效的token_應該返回false',
        15 => 'tests\\unit\\domains\\auth\\services\\testvalidaterefreshtoken_token已被撤銷_應該返回false',
        16 => 'tests\\unit\\domains\\auth\\services\\testrevokerefreshtoken_成功撤銷_應該返回true',
        17 => 'tests\\unit\\domains\\auth\\services\\testrevokerefreshtoken_撤銷失敗_應該返回false',
        18 => 'tests\\unit\\domains\\auth\\services\\testrevokeallusertokens_成功撤銷_應該返回撤銷數量',
        19 => 'tests\\unit\\domains\\auth\\services\\testrevokedevicetokens_成功撤銷_應該返回撤銷數量',
        20 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstats_成功取得統計_應該返回統計資料',
        21 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstats_發生例外_應該返回預設統計',
        22 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokens_成功清理_應該返回清理數量',
        23 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokens_沒有指定日期_應該使用預設日期',
        24 => 'tests\\unit\\domains\\auth\\services\\testcleanuprevokedtokens_成功清理_應該返回清理數量',
        25 => 'tests\\unit\\domains\\auth\\services\\testcleanuprevokedtokens_使用預設天數_應該清理30天前的記錄',
        26 => 'tests\\unit\\domains\\auth\\services\\createmocktokenpair',
        27 => 'tests\\unit\\domains\\auth\\services\\createmockjwtpayload',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php' => 
    array (
      0 => 'da3d6a72636272895dc381df6a243c1f2a430722',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\tokenblacklistrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistsuccess',
        2 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistfailure',
        3 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistduplicatekey',
        4 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistotherexception',
        5 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedtrue',
        6 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedfalse',
        7 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedwithexception',
        8 => 'tests\\unit\\infrastructure\\auth\\repositories\\testistokenhashblacklistedtrue',
        9 => 'tests\\unit\\infrastructure\\auth\\repositories\\testremovefromblacklistsuccess',
        10 => 'tests\\unit\\infrastructure\\auth\\repositories\\testremovefromblacklistnotfound',
        11 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjtifound',
        12 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjtinotfound',
        13 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid',
        14 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuseridwithexception',
        15 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbydeviceid',
        16 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyreason',
        17 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistsuccess',
        18 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistempty',
        19 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistwithfailures',
        20 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistwithexception',
        21 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedsuccess',
        22 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedempty',
        23 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedwithexception',
        24 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchremovefromblacklistsuccess',
        25 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchremovefromblacklistempty',
        26 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistallusertokenssuccess',
        27 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistallusertokensnotokens',
        28 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistalldevicetokenssuccess',
        29 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupsuccess',
        30 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupwithbeforedate',
        31 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupexpiredentries',
        32 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupoldentries',
        33 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetblackliststatssuccess',
        34 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetuserblackliststatssuccess',
        35 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetrecentblacklistentriessuccess',
        36 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgethighpriorityentriessuccess',
        37 => 'tests\\unit\\infrastructure\\auth\\repositories\\testsearchsuccess',
        38 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcountsearchsuccess',
        39 => 'tests\\unit\\infrastructure\\auth\\repositories\\testissizeexceededtrue',
        40 => 'tests\\unit\\infrastructure\\auth\\repositories\\testissizeexceededfalse',
        41 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsizeinfosuccess',
        42 => 'tests\\unit\\infrastructure\\auth\\repositories\\testoptimizesuccess',
        43 => 'tests\\unit\\infrastructure\\auth\\repositories\\testoptimizewithexception',
        44 => 'tests\\unit\\infrastructure\\auth\\repositories\\createsampleentry',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/TokenBlacklistServiceTest.php' => 
    array (
      0 => 'c88951a72b2baca0e370537fb1703c197256eecf',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\tokenblacklistservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokensuccessfully',
        3 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithhighpriorityreason',
        4 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithinvalidtokentype',
        5 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithinvalidreason',
        6 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenrepositoryfailure',
        7 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenrepositoryexception',
        8 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedsuccessfully',
        9 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedwithemptyjti',
        10 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedwithrepositoryexception',
        11 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistsuccessfully',
        12 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithemptyarray',
        13 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithemptyjtis',
        14 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithrepositoryexception',
        15 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenssuccessfully',
        16 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithinvaliduserid',
        17 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithinvalidreason',
        18 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithrepositoryexception',
        19 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenssuccessfully',
        20 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenswithemptydeviceid',
        21 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenswithrepositoryexception',
        22 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistsuccessfully',
        23 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistwithemptyjti',
        24 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistwithrepositoryexception',
        25 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistsuccessfully',
        26 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistwithemptyarray',
        27 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistwithrepositoryexception',
        28 => 'tests\\unit\\domains\\auth\\services\\testautocleanupsuccessfully',
        29 => 'tests\\unit\\domains\\auth\\services\\testautocleanupwithrepositoryexception',
        30 => 'tests\\unit\\domains\\auth\\services\\testgetstatisticssuccessfully',
        31 => 'tests\\unit\\domains\\auth\\services\\testgetstatisticswithrepositoryexception',
        32 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticssuccessfully',
        33 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticswithinvaliduserid',
        34 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticswithrepositoryexception',
        35 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentriessuccessfully',
        36 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithnegativeoffset',
        37 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithzerolimit',
        38 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithrepositoryexception',
        39 => 'tests\\unit\\domains\\auth\\services\\testgetrecenthighpriorityentriessuccessfully',
        40 => 'tests\\unit\\domains\\auth\\services\\testgetrecenthighpriorityentrieswithrepositoryexception',
        41 => 'tests\\unit\\domains\\auth\\services\\testoptimizesuccessfully',
        42 => 'tests\\unit\\domains\\auth\\services\\testoptimizewithrepositoryexception',
        43 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatushealthy',
        44 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatusunhealthywithrecommendations',
        45 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatuswithrepositoryexception',
        46 => 'tests\\unit\\domains\\auth\\services\\testservicewithoutlogger',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtAuthenticationIntegrationTest.php' => 
    array (
      0 => '598901c7bf8cd01c16ff24437bce9ff591be8d35',
      1 => 
      array (
        0 => 'tests\\integration\\jwtauthenticationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\testcanperformcompleteloginflow',
        3 => 'tests\\integration\\testcanrefreshtokenssuccessfully',
        4 => 'tests\\integration\\testcanlogoutandblacklisttokens',
        5 => 'tests\\integration\\testcanmanagemultipledevicelogins',
        6 => 'tests\\integration\\testcanhandleinvalidcredentials',
        7 => 'tests\\integration\\testcancleanupexpiredblacklistentries',
        8 => 'tests\\integration\\testcanperformhealthcheck',
        9 => 'tests\\integration\\createjwttokenservice',
        10 => 'tests\\integration\\setupuserrepositorymock',
        11 => 'tests\\integration\\createtestuser',
        12 => 'tests\\integration\\generatetestprivatekey',
        13 => 'tests\\integration\\generatetestpublickey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtAuthenticationIntegrationTest_simple.php' => 
    array (
      0 => 'dbf8cea580141ca1ff80c929bc01119fa168c125',
      1 => 
      array (
        0 => 'tests\\integration\\jwtauthenticationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\canmanagerefreshtokens',
        2 => 'tests\\integration\\canmanagetokenblacklist',
        3 => 'tests\\integration\\canusetokenblacklistservice',
        4 => 'tests\\integration\\canautocleanupexpiredentries',
        5 => 'tests\\integration\\canperformbatchoperations',
        6 => 'tests\\integration\\createtestuser',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtTokenBlacklistIntegrationTest.php' => 
    array (
      0 => 'af9789a2af6f7ad9c01a68cb3fef697ac4817f8c',
      1 => 
      array (
        0 => 'tests\\integration\\jwttokenblacklistintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\testbasicblacklistintegration',
        2 => 'tests\\integration\\testserviceblacklistoperations',
        3 => 'tests\\integration\\testbatchoperationsintegration',
        4 => 'tests\\integration\\teststatisticsintegration',
        5 => 'tests\\integration\\testautocleanupintegration',
        6 => 'tests\\integration\\testqueryfunctionsintegration',
        7 => 'tests\\integration\\testerrorhandlingintegration',
        8 => 'tests\\integration\\createtokenblacklisttable',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/AuthServiceTest.php' => 
    array (
      0 => 'ebe538048944a708ba67b228566385805aa377df',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\authservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\test_register_traditional_mode_without_jwt',
        3 => 'tests\\unit\\domains\\auth\\services\\test_register_jwt_mode_with_tokens',
        4 => 'tests\\unit\\domains\\auth\\services\\test_register_jwt_mode_token_generation_failure',
        5 => 'tests\\unit\\domains\\auth\\services\\test_login_traditional_mode_success',
        6 => 'tests\\unit\\domains\\auth\\services\\test_login_jwt_mode_success',
        7 => 'tests\\unit\\domains\\auth\\services\\test_login_user_not_found',
        8 => 'tests\\unit\\domains\\auth\\services\\test_login_user_disabled',
        9 => 'tests\\unit\\domains\\auth\\services\\test_login_invalid_password',
        10 => 'tests\\unit\\domains\\auth\\services\\test_logout_traditional_mode',
        11 => 'tests\\unit\\domains\\auth\\services\\test_logout_jwt_mode_success',
        12 => 'tests\\unit\\domains\\auth\\services\\test_logout_jwt_mode_revocation_failure',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/RefreshTokenServiceTest.php' => 
    array (
      0 => 'b20aee0749cd1c4bf8ead4e92a48046b8d957d65',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\refreshtokenservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\testservicecanbeinstantiated',
        3 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokenssuccess',
        4 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokensexception',
        5 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstatssuccess',
        6 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstatsexception',
        7 => 'tests\\unit\\domains\\auth\\services\\testrevoketokensuccess',
        8 => 'tests\\unit\\domains\\auth\\services\\testrevoketokenexception',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Application/Middleware/JwtAuthorizationMiddlewareTest.php' => 
    array (
      0 => '3fde280f9a6f8caa871513c82e5aa4049c9d1493',
      1 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\jwtauthorizationmiddlewaretest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\setup',
        1 => 'tests\\unit\\application\\middleware\\teardown',
        2 => 'tests\\unit\\application\\middleware\\testmiddlewareisdisabledwhennotenabled',
        3 => 'tests\\unit\\application\\middleware\\testskipsprocessingfornonapipaths',
        4 => 'tests\\unit\\application\\middleware\\testreturns403whenusernotauthenticated',
        5 => 'tests\\unit\\application\\middleware\\testallowssuperadminaccess',
        6 => 'tests\\unit\\application\\middleware\\testallowsaccesswithvalidrolepermissions',
        7 => 'tests\\unit\\application\\middleware\\testdeniesaccesswithinvalidrolepermissions',
        8 => 'tests\\unit\\application\\middleware\\testallowsaccesswithvaliddirectpermissions',
        9 => 'tests\\unit\\application\\middleware\\testmiddlewarepriorityandenabledsettings',
        10 => 'tests\\unit\\application\\middleware\\createrequest',
        11 => 'tests\\unit\\application\\middleware\\createauthenticatedrequest',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Application/Middleware/AuthorizationResultTest.php' => 
    array (
      0 => '9f955b4470242684a882c1dfe0327d9ff6d6ebfa',
      1 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\authorizationresulttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\testcancreateallowedresult',
        1 => 'tests\\unit\\application\\middleware\\testcancreatedeniedresult',
        2 => 'tests\\unit\\application\\middleware\\teststaticallowmethod',
        3 => 'tests\\unit\\application\\middleware\\teststaticdenymethod',
        4 => 'tests\\unit\\application\\middleware\\teststaticallowsuperadminmethod',
        5 => 'tests\\unit\\application\\middleware\\teststaticdenyinsufficientpermissionsmethod',
        6 => 'tests\\unit\\application\\middleware\\teststaticdenynotauthenticatedmethod',
        7 => 'tests\\unit\\application\\middleware\\teststaticdenyiprestrictionmethod',
        8 => 'tests\\unit\\application\\middleware\\teststaticdenytimerestrictionmethod',
        9 => 'tests\\unit\\application\\middleware\\testhasrulemethod',
        10 => 'tests\\unit\\application\\middleware\\testtoarraymethod',
        11 => 'tests\\unit\\application\\middleware\\testjsonserializableinterface',
        12 => 'tests\\unit\\application\\middleware\\testequalsmethod',
        13 => 'tests\\unit\\application\\middleware\\testtostringmethod',
        14 => 'tests\\unit\\application\\middleware\\testtostringmethodfordeniedresult',
        15 => 'tests\\unit\\application\\middleware\\testdefaultparametersforstaticmethods',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Application/Middleware/JwtAuthenticationMiddlewareTest.php' => 
    array (
      0 => 'ea0118b1f7fc4874d2e590609900f26e6be1c986',
      1 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\jwtauthenticationmiddlewaretest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\application\\middleware\\setup',
        1 => 'tests\\unit\\application\\middleware\\teardown',
        2 => 'tests\\unit\\application\\middleware\\testshouldskipprocessingforpublicpaths',
        3 => 'tests\\unit\\application\\middleware\\testshouldprocessauthenticatedpaths',
        4 => 'tests\\unit\\application\\middleware\\testshouldreturnunauthorizedwhennotokenprovided',
        5 => 'tests\\unit\\application\\middleware\\testshouldextracttokenfromauthorizationheader',
        6 => 'tests\\unit\\application\\middleware\\testshouldextracttokenfromqueryparameter',
        7 => 'tests\\unit\\application\\middleware\\testshouldextracttokenfromcookie',
        8 => 'tests\\unit\\application\\middleware\\testshouldinjectusercontextintorequest',
        9 => 'tests\\unit\\application\\middleware\\testshouldreturnunauthorizedwhentokenexpired',
        10 => 'tests\\unit\\application\\middleware\\testshouldreturnunauthorizedwhentokeninvalid',
        11 => 'tests\\unit\\application\\middleware\\testshouldvalidateipaddresswhenpresentintoken',
        12 => 'tests\\unit\\application\\middleware\\testshouldfailwhenipaddressmismatch',
        13 => 'tests\\unit\\application\\middleware\\testshouldrejectwhenipaddressmismatch',
        14 => 'tests\\unit\\application\\middleware\\testshouldvalidatedevicefingerprint',
        15 => 'tests\\unit\\application\\middleware\\testshouldrejectwhendevicefingerprintmismatch',
        16 => 'tests\\unit\\application\\middleware\\testshouldskipprocessingwhendisabled',
        17 => 'tests\\unit\\application\\middleware\\testshouldhandlegenericexceptiongracefully',
        18 => 'tests\\unit\\application\\middleware\\testshouldprioritizeauthorizationheaderoverothermethods',
        19 => 'tests\\unit\\application\\middleware\\testcangetandsetpriority',
        20 => 'tests\\unit\\application\\middleware\\testcangetname',
        21 => 'tests\\unit\\application\\middleware\\testcansetenabled',
        22 => 'tests\\unit\\application\\middleware\\createvalidpayload',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Api/V1/AuthEndpointTest.php' => 
    array (
      0 => '24029f5e2376ea6205735b7e662d651bfa5d9c01',
      1 => 
      array (
        0 => 'tests\\integration\\api\\v1\\authendpointtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\api\\v1\\setup',
        1 => 'tests\\integration\\api\\v1\\createrequest',
        2 => 'tests\\integration\\api\\v1\\testloginendpointreturnsjwttokens',
        3 => 'tests\\integration\\api\\v1\\testrefreshtokenendpoint',
        4 => 'tests\\integration\\api\\v1\\testlogoutendpoint',
        5 => 'tests\\integration\\api\\v1\\testprotectedendpointwithouttoken',
        6 => 'tests\\integration\\api\\v1\\testprotectedendpointwithinvalidtoken',
        7 => 'tests\\integration\\api\\v1\\testapplicationbootstrap',
        8 => 'tests\\integration\\api\\v1\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Performance/JwtPerformanceTest.php' => 
    array (
      0 => '5ce71fb06f8a6bdd70a56e18546d2bb876c17c0c',
      1 => 
      array (
        0 => 'tests\\performance\\jwtperformancetest',
      ),
      2 => 
      array (
        0 => 'tests\\performance\\setup',
        1 => 'tests\\performance\\testjwttokengenerationperformance',
        2 => 'tests\\performance\\testjwttokenvalidationperformance',
        3 => 'tests\\performance\\testconcurrenttokengeneration',
        4 => 'tests\\performance\\testmemoryusage',
        5 => 'tests\\performance\\testtokensize',
      ),
      3 => 
      array (
      ),
    ),
  ),
));