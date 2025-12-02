
(function() {
    'use strict';
    
    let translations = {};
    let translationsLoaded = false;
    
    function loadTranslations() {
        let lang = 'RU';
        
        if (typeof window.LR_LANG !== 'undefined') {
            lang = window.LR_LANG;
        } else if (typeof window.LANGUAGE !== 'undefined') {
            lang = window.LANGUAGE;
        } else if (document.documentElement.lang) {
            lang = document.documentElement.lang;
        } else {
            const htmlLang = document.documentElement.getAttribute('lang');
            if (htmlLang) {
                lang = htmlLang.toUpperCase();
            }
        }
        
        fetch('/app/modules/module_page_surf_records/translation.json')
            .then(response => response.json())
            .then(data => {
                translations = {};
                Object.keys(data).forEach(key => {
                    translations[key] = data[key][lang] || data[key]['RU'];
                });
                translationsLoaded = true;
            })
            .catch(error => {
                translationsLoaded = true;
            });
    }
    
    function t(key) {
        return (translationsLoaded && translations[key]) ? translations[key] : key;
    }
    
    loadTranslations();
    
    
    window.toggleMapsSidebar = function() {
        const sidebar = document.querySelector('.surf-maps-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    };
    
    
    window.selectMap = function(mapName) {
        loadMapRecords(mapName);
        updateActiveMap(mapName);
        updateURL(mapName);
    };
    
    
    window.openMapTab = function(event, tabName) {
        const contents = document.querySelectorAll('.maps-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.maps-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        const targetContent = document.getElementById('maps-' + tabName);
        if (targetContent) {
            targetContent.classList.add('active');
            
            const mapsList = targetContent.querySelector('.maps-list');
            if (mapsList && mapsList.dataset.loaded !== 'true') {
                loadMapsForCategory(tabName);
            }
        }
        
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
    };
    
    
    window.filterMaps = function() {
        const input = document.getElementById('map-search-input');
        if (!input) return;
        
        const filter = input.value.toUpperCase();
        const mapItems = document.querySelectorAll('.map-item');
        
        mapItems.forEach(item => {
            const mapName = item.getAttribute('data-map');
            if (mapName && mapName.toUpperCase().indexOf(filter) > -1) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    };
    
    
    window.toggleMobileMaps = function() {
        const content = document.querySelector('.mobile-maps-content');
        if (content) {
            content.classList.toggle('active');
        }
    };
    
    window.selectMobileMap = function(mapName) {
        loadMapRecords(mapName);
        updateActiveMap(mapName);
        updateURL(mapName);
        const mobileContent = document.querySelector('.mobile-maps-content');
        if (mobileContent) {
            mobileContent.classList.remove('active');
        }
    };
    
    window.openMobileMapTab = function(event, tabName) {
        const contents = document.querySelectorAll('.mobile-maps-list-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.mobile-maps-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        const targetContent = document.getElementById('mobile-maps-' + tabName);
        if (targetContent) {
            targetContent.classList.add('active');
        }
        
        event.currentTarget.classList.add('active');
    };
    
    
    
    function initMobileAutoClose() {
        if (window.innerWidth <= 768) {
            const mapItems = document.querySelectorAll('.map-item');
            const sidebar = document.querySelector('.surf-maps-sidebar');
            
            mapItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                });
            });
        }
    }
    
    function initOutsideClick() {
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.surf-maps-sidebar');
            const toggleBtn = document.querySelector('.maps-toggle-btn');
            
            if (sidebar && toggleBtn) {
                if (!sidebar.contains(e.target) && 
                    !toggleBtn.contains(e.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
    
    
    function initKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('.surf-maps-sidebar');
                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
            
            if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
                const searchInput = document.getElementById('map-search-input');
                if (searchInput && document.activeElement !== searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
        });
    }
    
    
    function highlightActiveMap() {
        const activeMapItem = document.querySelector('.map-item.active');
        if (activeMapItem) {
            activeMapItem.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            const parentContent = activeMapItem.closest('.maps-content');
            if (parentContent && !parentContent.classList.contains('active')) {
                const contentId = parentContent.id;
                const tabName = contentId.replace('maps-', '');
                const correspondingTab = document.querySelector(`.maps-tab[onclick*="${tabName}"]`);
                
                if (correspondingTab) {
                    document.querySelectorAll('.maps-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.maps-content').forEach(c => c.classList.remove('active'));
                    
                    correspondingTab.classList.add('active');
                    parentContent.classList.add('active');
                }
            }
        }
    }
    
    
    function initCopyLinks() {
        const steamLinks = document.querySelectorAll('.action-btn[href*="steamcommunity.com"]');
        steamLinks.forEach(link => {
            link.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                const url = link.getAttribute('href');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).catch(() => {});
                }
            });
        });
    }
    
    function loadMapRecords(mapName) {
        if (!mapName || typeof mapName !== 'string' || !/^[a-zA-Z0-9_-]+$/.test(mapName)) {
            return;
        }
        
        const leaderboardBody = document.querySelector('.leaderboard-table-body');
        const leaderboardTitle = document.querySelector('.leaderboard-title');
        const leaderboardCount = document.querySelector('.leaderboard-count');
        
        if (!leaderboardBody || !leaderboardTitle || !leaderboardCount) return;
        
        leaderboardBody.classList.add('updating');
        
        showLoadingIndicator(leaderboardBody);
        
        const apiUrl = `${window.location.origin}/app/modules/module_page_surf_records/api/index.php?endpoint=records&map=${encodeURIComponent(mapName)}`;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateLeaderboard(data.data, mapName);
                } else {
                    showError(t('_loading_records_error') + ': ' + data.error);
                }
            })
            .catch(error => {
                const url = new URL(window.location);
                url.searchParams.set('map', mapName);
                window.location.href = url.toString();
            })
            .finally(() => {
                leaderboardBody.classList.remove('updating');
            });
    }
    
    function showLoadingIndicator(container) {
        container.innerHTML = `
            <div class="loading-indicator">
                <div class="loading-spinner"></div>
                <p>Загрузка рекордов...</p>
            </div>
        `;
    }
    
    function updateLeaderboard(data, mapName) {
        const leaderboardBody = document.querySelector('.leaderboard-table-body');
        const leaderboardTitle = document.querySelector('.leaderboard-title');
        const leaderboardCount = document.querySelector('.leaderboard-count');
        
        if (!leaderboardBody || !leaderboardTitle || !leaderboardCount) {
            return;
        }
        
        leaderboardTitle.innerHTML = `
            <i class="fa-solid fa-ranking-star"></i>
            ${mapName}
        `;
        
        leaderboardCount.textContent = `${t('_total_records')} ${data.count}`;
        
        if (data.records && data.records.length > 0) {
            const fragment = document.createDocumentFragment();
            
            data.records.forEach(record => {
                if (!record.SteamID || !record.place) return;
                
                const row = document.createElement('div');
                row.className = `leaderboard-row ${record.place <= 3 ? 'top-' + record.place : ''}`;
                
                row.innerHTML = `
                    <div class="table-col col-place" data-label="${escapeHtml(t('_place_label'))}">
                        <span class="place-number place-${parseInt(record.place)}">
                            #${parseInt(record.place)}
                        </span>
                    </div>
                    <div class="table-col col-player" data-label="${escapeHtml(t('_player_label'))}">
                        <a href="/profiles/${escapeHtml(record.SteamID)}/?search=1" 
                           class="player-name-link"
                           title="${escapeHtml(t('_view_profile'))}">
                            ${escapeHtml(record.PlayerName)}
                        </a>
                    </div>
                    <div class="table-col col-time" data-label="${escapeHtml(t('_time'))}">
                        <span class="time-value">${escapeHtml(record.FormattedTime)}</span>
                    </div>
                    <div class="table-col col-actions">
                        <a href="https://steamcommunity.com/profiles/${escapeHtml(record.SteamID)}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="action-btn"
                           title="Steam">
                           <svg><use href="/resources/img/sprite.svg#steam"></use></svg>
                        </a>
                    </div>
                `;
                
                fragment.appendChild(row);
            });
            
            leaderboardBody.innerHTML = '';
            leaderboardBody.appendChild(fragment);
        } else {
            leaderboardBody.innerHTML = `
                <div class="no-records">
                    <i class="fa-solid fa-inbox"></i>
                    <p>${escapeHtml(t('_no_records'))}</p>
                </div>
            `;
        }

        initCopyLinks();
    }
    
    function updateActiveMap(mapName) {
        if (!mapName || typeof mapName !== 'string') return;
        
        document.querySelectorAll('.map-item, .mobile-map-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const activeItem = document.querySelector(`[data-map="${CSS.escape(mapName)}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }
    
    function updateURL(mapName) {
        const url = new URL(window.location);
        url.searchParams.set('map', mapName);
        window.history.pushState({}, '', url.toString());
    }
    
    function showError(message) {
        const leaderboardBody = document.querySelector('.leaderboard-table-body');
        if (leaderboardBody) {
            leaderboardBody.innerHTML = `
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <p>${escapeHtml(message)}</p>
                </div>
            `;
        }
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
    
    function loadMapsForCategory(category) {
        if (!category || typeof category !== 'string' || !['surf', 'kz', 'bhop', 'other'].includes(category)) {
            return;
        }
        
        const mapsList = document.querySelector(`#maps-${CSS.escape(category)} .maps-list`);
        if (!mapsList || mapsList.dataset.loaded === 'true') {
            return;
        }
        
        const originalContent = mapsList.innerHTML;
        mapsList.innerHTML = `<li class="loading-maps">${escapeHtml(t('_loading_maps'))}</li>`;
        
        fetch(`${window.location.origin}/app/modules/module_page_surf_records/api/index.php?endpoint=maps&category=${encodeURIComponent(category)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data[category] && data.data[category].length > 0) {
                    const fragment = document.createDocumentFragment();
                    
                    data.data[category].forEach(map => {
                        if (typeof map === 'string' && /^[a-zA-Z0-9_-]+$/.test(map)) {
                            const li = document.createElement('li');
                            li.className = 'map-item';
                            li.setAttribute('data-map', map);
                            li.textContent = map;
                            li.onclick = () => selectMap(map);
                            fragment.appendChild(li);
                        }
                    });
                    
                    mapsList.innerHTML = '';
                    mapsList.appendChild(fragment);
                    mapsList.dataset.loaded = 'true';
                } else {
                    mapsList.innerHTML = originalContent;
                }
            })
            .catch(error => {
                mapsList.innerHTML = originalContent;
            });
    }
    
    function initBrowserHistory() {
        window.addEventListener('popstate', function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const mapName = urlParams.get('map');
            if (mapName) {
                loadMapRecords(mapName);
                updateActiveMap(mapName);
            }
        });
    }
    
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        initMobileAutoClose();
        initOutsideClick();
        initKeyboardNavigation();
        highlightActiveMap();
        initCopyLinks();
        initBrowserHistory();
    }
    
    init();
    
    
    window.SurfRecordsModule = {
        selectMap: window.selectMap,
        toggleSidebar: window.toggleMapsSidebar,
        filterMaps: window.filterMaps,
        openTab: window.openMapTab,
        toggleMobileMaps: window.toggleMobileMaps,
        selectMobileMap: window.selectMobileMap,
        openMobileMapTab: window.openMobileMapTab
    };
    
})();
