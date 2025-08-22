# 專案快照 (Tue Jun 24 05:31:22 PM CST 2025)

## 目錄結構 (最大深度: 4)
```
.
├── database
│   └── migrations
├── public
│   ├── assets
│   ├── css
│   ├── images
│   └── js
├── resources
│   ├── css
│   ├── js
│   └── views
├── scripts
├── src
│   ├── Controllers
│   ├── Database
│   ├── Exceptions
│   ├── Helpers
│   ├── Middleware
│   ├── Models
│   ├── Repositories
│   │   └── Contracts
│   └── Services
│       ├── Contracts
│       ├── Enums
│       ├── Security
│       │   └── Contracts
│       └── Validators
└── tests
    ├── Factory
    │   └── Abstracts
    ├── Integration
    ├── Security
    ├── UI
    └── Unit
        ├── Controllers
        ├── Database
        ├── Factory
        ├── Models
        ├── Repositories
        ├── Repository
        └── Services
            ├── Enums
            └── Security

44 directories
```

## 原始碼 ()
```plaintext
  - 定義: NotFoundException
  - 定義: StateTransitionException
  - 定義: CsrfTokenException
  - 方法: __construct()
  - 定義: ValidationException
  - 方法: __construct()
  - 方法: getErrors()
  - 定義: IpController
  - 方法: __construct()
  - 方法: create()
  - 方法: getByType()
  - 方法: checkAccess()
  - 定義: AttachmentController
  - 方法: __construct()
  - 方法: upload()
  - 方法: list()
  - 方法: delete()
  - 定義: AuthController
  - 方法: __construct()
  - 方法: register()
  - 方法: login()
  - 方法: logout()
  - 定義: PostController
  - 方法: __construct()
  - 方法: index()
  - 方法: show()
  - 方法: store()
  - 方法: update()
  - 方法: destroy()
  - 方法: updatePinStatus()
  - 定義: Post
  - 方法: __construct()
  - 方法: getId()
  - 方法: getUuid()
  - 方法: getSeqNumber()
  - 方法: getTitle()
  - 方法: getContent()
  - 方法: getUserId()
  - 方法: getUserIp()
  - 方法: isPinned()
  - 方法: getIsPinned()
  - 方法: getStatus()
  - 方法: getPublishDate()
  - 方法: getViews()
  - 方法: getViewCount()
  - 方法: getCreatedAt()
  - 方法: getUpdatedAt()
  - 方法: toArray()
  - 方法: jsonSerialize()
  - 定義: Attachment
  - 方法: __construct()
  - 方法: getId()
  - 方法: getUuid()
  - 方法: getPostId()
  - 方法: getFilename()
  - 方法: getOriginalName()
  - 方法: getMimeType()
  - 方法: getFileSize()
  - 方法: getStoragePath()
  - 方法: getCreatedAt()
  - 方法: getUpdatedAt()
  - 方法: getDeletedAt()
  - 方法: toArray()
  - 定義: IpList
  - 方法: __construct()
  - 方法: getId()
  - 方法: getUuid()
  - 方法: getIpAddress()
  - 方法: getType()
  - 方法: getUnitId()
  - 方法: getDescription()
  - 方法: getCreatedAt()
  - 方法: getUpdatedAt()
  - 方法: isWhitelist()
  - 方法: isBlacklist()
  - 方法: toArray()
  - 方法: jsonSerialize()
  - 定義: DatabaseConnection
  - 定義: IpService
  - 方法: __construct()
  - 方法: createIpRule()
  - 方法: isIpAllowed()
  - 方法: getRulesByType()
  - 定義: CacheService
  - 方法: __construct()
  - 方法: get()
  - 方法: set()
  - 方法: delete()
  - 方法: clear()
  - 方法: remember()
  - 定義: RateLimitService
  - 方法: __construct()
  - 方法: checkLimit()
  - 方法: isAllowed()
  - 定義: XssProtectionService
  - 方法: clean()
  - 方法: cleanArray()
  - 定義: XssProtectionServiceInterface
  - 方法: sanitize()
  - 方法: sanitizeArray()
  - 方法: cleanArray()
  - 定義: CsrfProtectionServiceInterface
  - 方法: generateToken()
  - 方法: validateToken()
  - 方法: getTokenFromRequest()
  - 定義: CsrfProtectionService
  - 方法: generateToken()
  - 方法: validateToken()
  - 方法: getLabel()
  - 方法: canTransitionTo()
  - 定義: FileRules
  - 定義: AttachmentService
  - 方法: __construct()
  - 方法: validateFile()
  - 方法: upload()
  - 方法: download()
  - 方法: delete()
  - 方法: getByPostId()
  - 定義: PostServiceInterface
  - 方法: createPost()
  - 方法: updatePost()
  - 方法: deletePost()
  - 方法: findById()
  - 方法: listPosts()
  - 方法: getPinnedPosts()
  - 方法: setPinned()
  - 方法: setTags()
  - 方法: recordView()
  - 定義: AttachmentServiceInterface
  - 方法: upload()
  - 方法: download()
  - 方法: delete()
  - 方法: validateFile()
  - 定義: PostValidator
  - 方法: validate()
  - 定義: PostService
  - 方法: __construct()
  - 方法: createPost()
  - 方法: updatePost()
  - 方法: deletePost()
  - 方法: getPost()
  - 方法: findById()
  - 方法: listPosts()
  - 方法: getPinnedPosts()
  - 方法: setPinned()
  - 方法: setTags()
  - 方法: recordView()
  - 定義: AuthService
  - 方法: __construct()
  - 方法: register()
  - 方法: login()
  - 定義: RateLimitMiddleware
  - 方法: __construct()
  - 方法: process()
  - 定義: IpRepository
  - 方法: __construct()
  - 方法: create()
  - 方法: find()
  - 方法: findByUuid()
  - 方法: findByIpAddress()
  - 方法: update()
  - 方法: delete()
  - 方法: getByType()
  - 方法: paginate()
  - 方法: isBlacklisted()
  - 方法: isWhitelisted()
  - 定義: UserRepository
  - 方法: __construct()
  - 方法: create()
  - 方法: update()
  - 方法: delete()
  - 方法: findById()
  - 方法: findByUuid()
  - 方法: findByUsername()
  - 方法: findByEmail()
  - 方法: updateLastLogin()
  - 方法: updatePassword()
  - 定義: PostRepository
  - 方法: __construct()
  - 方法: find()
  - 方法: findByUuid()
  - 方法: findBySeqNumber()
  - 方法: create()
  - 方法: update()
  - 方法: delete()
  - 方法: paginate()
  - 方法: getPinnedPosts()
  - 方法: getPostsByTag()
  - 方法: incrementViews()
  - 方法: setPinned()
  - 方法: setTags()
  - 方法: searchByTitle()
  - 方法: findByUserId()
  - 方法: search()
  - 定義: RepositoryInterface
  - 方法: find()
  - 方法: findByUuid()
  - 方法: create()
  - 方法: update()
  - 方法: delete()
  - 方法: paginate()
  - 定義: IpRepositoryInterface
  - 方法: findByIpAddress()
  - 方法: getByType()
  - 方法: isBlacklisted()
  - 方法: isWhitelisted()
  - 定義: PostRepositoryInterface
  - 方法: findBySeqNumber()
  - 方法: getPinnedPosts()
  - 方法: getPostsByTag()
  - 方法: incrementViews()
  - 方法: setPinned()
  - 方法: setTags()
  - 定義: AttachmentRepository
  - 方法: __construct()
  - 方法: create()
  - 方法: find()
  - 方法: findByUuid()
  - 方法: getByPostId()
  - 方法: delete()
```

## Helper 函式 ()
```plaintext
```

## 資料庫遷移 ()
```plaintext
```

## 視圖 ()
```plaintext
```

## 測試 ()
```plaintext
```

## 關鍵設定檔
```plaintext
- composer.json
- env.example
- docker-compose.yml
- phpunit.xml
```
