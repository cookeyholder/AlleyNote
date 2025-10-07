/**
 * Service Worker
 * 提供離線支援和快取策略
 * 
 * @author AlleyNote Team
 * @version 1.2.0
 */

const CACHE_VERSION = 'v1.2.0';  // 每次更新遞增
const CACHE_NAME = `alleynote-${CACHE_VERSION}`;
const RUNTIME_CACHE = `alleynote-runtime-${CACHE_VERSION}`;

// 需要快取的靜態資源
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/style.css',
  '/src/main.js',
  '/offline.html'
];

// API 快取白名單
const API_CACHE_WHITELIST = [
  '/api/posts',
  '/api/statistics'
];

/**
 * 安裝事件
 * 預快取靜態資源
 */
self.addEventListener('install', (event) => {
  console.log('[SW] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Installed successfully');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[SW] Installation failed:', error);
      })
  );
});

/**
 * 激活事件
 * 清理舊快取
 */
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(cacheName => {
              return cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE;
            })
            .map(cacheName => {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('[SW] Activated successfully');
        return self.clients.claim();
      })
  );
});

/**
 * 攔截請求事件
 * 實作快取策略
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // 跳過非同源請求（除了 API）
  if (url.origin !== self.location.origin && !url.pathname.startsWith('/api')) {
    return;
  }

  // 跳過 POST、PUT、DELETE 請求
  if (request.method !== 'GET') {
    return;
  }

  // API 請求：Network First 策略
  if (url.pathname.startsWith('/api')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // 靜態資源：Cache First 策略
  event.respondWith(cacheFirst(request));
});

/**
 * Cache First 策略
 * 優先使用快取，快取不存在時才發送網路請求
 * 適用於：靜態資源（HTML、CSS、JS、圖片等）
 */
async function cacheFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  
  if (cached) {
    console.log('[SW] Cache hit:', request.url);
    return cached;
  }
  
  try {
    console.log('[SW] Fetching from network:', request.url);
    const response = await fetch(request);
    
    // 只快取成功的回應
    if (response && response.status === 200) {
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.error('[SW] Fetch failed:', error);
    
    // 如果是導航請求，返回離線頁面
    if (request.mode === 'navigate') {
      return caches.match('/offline.html');
    }
    
    throw error;
  }
}

/**
 * Network First 策略
 * 優先使用網路，網路失敗時才使用快取
 * 適用於：API 請求、動態內容
 */
async function networkFirst(request) {
  const cache = await caches.open(RUNTIME_CACHE);
  
  try {
    console.log('[SW] Fetching from network:', request.url);
    const response = await fetch(request);
    
    // 快取成功的回應
    if (response && response.status === 200) {
      // 只快取白名單中的 API
      const url = new URL(request.url);
      const shouldCache = API_CACHE_WHITELIST.some(pattern => 
        url.pathname.startsWith(pattern)
      );
      
      if (shouldCache) {
        cache.put(request, response.clone());
      }
    }
    
    return response;
  } catch (error) {
    console.error('[SW] Network failed, trying cache:', error);
    
    const cached = await cache.match(request);
    
    if (cached) {
      console.log('[SW] Cache hit:', request.url);
      return cached;
    }
    
    throw error;
  }
}

/**
 * Stale While Revalidate 策略
 * 立即返回快取，同時在背景更新快取
 * 適用於：不需要即時性的資源
 */
async function staleWhileRevalidate(request) {
  const cache = await caches.open(RUNTIME_CACHE);
  const cached = await cache.match(request);
  
  // 啟動背景更新
  const fetchPromise = fetch(request)
    .then(response => {
      if (response && response.status === 200) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(error => {
      console.error('[SW] Background fetch failed:', error);
    });
  
  // 如果有快取，立即返回；否則等待網路請求
  return cached || fetchPromise;
}

/**
 * 監聽訊息事件
 * 用於從主執行緒控制 Service Worker
 */
self.addEventListener('message', (event) => {
  const { type, payload } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'CLEAR_CACHE':
      handleClearCache(payload);
      break;
      
    case 'CACHE_URLS':
      handleCacheUrls(payload);
      break;
      
    default:
      console.log('[SW] Unknown message type:', type);
  }
});

/**
 * 清除快取
 */
async function handleClearCache(payload) {
  const { cacheName } = payload || {};
  
  if (cacheName) {
    await caches.delete(cacheName);
    console.log('[SW] Cache cleared:', cacheName);
  } else {
    const keys = await caches.keys();
    await Promise.all(keys.map(key => caches.delete(key)));
    console.log('[SW] All caches cleared');
  }
  
  // 通知客戶端
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage({
        type: 'CACHE_CLEARED',
        payload: { cacheName }
      });
    });
  });
}

/**
 * 快取指定的 URLs
 */
async function handleCacheUrls(payload) {
  const { urls } = payload || {};
  
  if (!urls || !Array.isArray(urls)) {
    return;
  }
  
  const cache = await caches.open(RUNTIME_CACHE);
  
  await Promise.all(
    urls.map(url => 
      fetch(url)
        .then(response => {
          if (response && response.status === 200) {
            return cache.put(url, response);
          }
        })
        .catch(error => {
          console.error('[SW] Failed to cache URL:', url, error);
        })
    )
  );
  
  console.log('[SW] URLs cached:', urls.length);
}

/**
 * 監聽同步事件（Background Sync）
 * 用於在網路恢復時重試失敗的請求
 */
self.addEventListener('sync', (event) => {
  console.log('[SW] Sync event:', event.tag);
  
  if (event.tag === 'sync-posts') {
    event.waitUntil(syncPosts());
  }
});

/**
 * 同步文章資料
 */
async function syncPosts() {
  try {
    console.log('[SW] Syncing posts...');
    
    // 從 IndexedDB 或快取中取得待同步的資料
    // 這裡需要根據實際需求實作
    
    console.log('[SW] Posts synced successfully');
  } catch (error) {
    console.error('[SW] Sync failed:', error);
    throw error;
  }
}

/**
 * 監聽推送通知事件
 */
self.addEventListener('push', (event) => {
  console.log('[SW] Push received');
  
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'AlleyNote';
  const options = {
    body: data.body || '您有新的通知',
    icon: '/icon-192.png',
    badge: '/icon-72.png',
    data: data.url || '/',
    ...data.options
  };
  
  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

/**
 * 監聽通知點擊事件
 */
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked');
  
  event.notification.close();
  
  const url = event.notification.data || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        // 如果已有視窗開啟，聚焦並導航
        for (let client of clientList) {
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }
        
        // 否則開啟新視窗
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

console.log('[SW] Service Worker script loaded');
