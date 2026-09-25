<!-- Interactive Route Verification Modal -->
<div x-data="routeModal" @open-route.window="openRouteModal($event.detail.origin, $event.detail.destination)">
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('routeModal', () => ({
                isRouteModalOpen: false,
                routeOrigin: '',
                routeDestination: '',
                mapInitialized: false,
                
                openRouteModal(origin, destination) {
                    this.routeOrigin = origin;
                    this.routeDestination = destination;
                    this.isRouteModalOpen = true;
                    
                    setTimeout(() => {
                        this.loadGoogleMapsScript();
                    }, 300);
                },
                
                loadGoogleMapsScript() {
                    if (typeof google !== 'undefined' && google.maps && google.maps.DirectionsService) {
                        this.initMap();
                        return;
                    }
                    
                    if (document.getElementById('google-maps-script')) {
                        setTimeout(() => this.initMap(), 500);
                        return;
                    }
                    
                    const script = document.createElement('script');
                    script.id = 'google-maps-script';
                    script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config("services.google.maps_api_key") }}&libraries=places,geometry,marker&loading=async';
                    script.async = true;
                    script.defer = true;
                    script.onload = () => {
                        this.initMap();
                    };
                    script.onerror = () => {
                        this.initMap();
                    };
                    document.head.appendChild(script);
                },
                
                initMap() {
                    if (this.mapInitialized) return;
                    
                    const mapContainer = document.getElementById('map-container');
                    if (!mapContainer) return;
                    
                    if (typeof google !== 'undefined' && google.maps && google.maps.DirectionsService) {
                        this.initGoogleMaps(mapContainer);
                    } else {
                        this.initLeafletMap(mapContainer);
                    }
                    this.mapInitialized = true;
                },
                
                initGoogleMaps(container) {
                    const map = new google.maps.Map(container, {
                        zoom: 12,
                        center: { lat: 3.1390, lng: 101.6869 },
                        mapId: 'DEMO_MAP_ID'
                    });
                    
                    const requestBody = {
                        origin: { address: this.routeOrigin },
                        destination: { address: this.routeDestination },
                        travelMode: 'DRIVE'
                    };

                    fetch('https://routes.googleapis.com/directions/v2:computeRoutes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Goog-Api-Key': '{{ config("services.google.maps_api_key") }}',
                            'X-Goog-FieldMask': 'routes.distanceMeters,routes.polyline.encodedPolyline,routes.viewport'
                        },
                        body: JSON.stringify(requestBody)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Routes API Error:', data.error.message);
                            this.initLeafletMap(container);
                            return;
                        }

                        if (data.routes && data.routes.length > 0) {
                            const route = data.routes[0];
                            const decodedPath = google.maps.geometry.encoding.decodePath(route.polyline.encodedPolyline);
                            
                            const polyline = new google.maps.Polyline({
                                path: decodedPath,
                                geodesic: true,
                                strokeColor: '#3b82f6',
                                strokeOpacity: 0.8,
                                strokeWeight: 5
                            });
                            polyline.setMap(map);

                            if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                                new google.maps.marker.AdvancedMarkerElement({
                                    position: decodedPath[0],
                                    map: map,
                                    title: 'Origin'
                                });
                                new google.maps.marker.AdvancedMarkerElement({
                                    position: decodedPath[decodedPath.length - 1],
                                    map: map,
                                    title: 'Destination'
                                });
                            }

                            if (route.viewport) {
                                const bounds = new google.maps.LatLngBounds(
                                    new google.maps.LatLng(route.viewport.low.latitude, route.viewport.low.longitude),
                                    new google.maps.LatLng(route.viewport.high.latitude, route.viewport.high.longitude)
                                );
                                map.fitBounds(bounds, 40);
                            }
                        } else {
                            console.error('Routes API returned no routes.');
                            this.initLeafletMap(container);
                        }
                    })
                    .catch(err => {
                        console.error('Fetch to Routes API failed:', err);
                        this.initLeafletMap(container);
                    });
                },
                
                initLeafletMap(container) {
                    container.innerHTML = `<div class="p-4 text-center text-slate-500 font-bold flex flex-col items-center justify-center h-full">
                        <i class="fa-solid fa-map-location-dot text-4xl mb-4 text-slate-300"></i>
                        <p>Route Map visualization is limited because Google Maps API is unavailable.</p>
                        <p class="text-xs font-normal mt-2">Please use the external link to view the route.</p>
                        <a href="https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(this.routeOrigin)}&destination=${encodeURIComponent(this.routeDestination)}&travelmode=driving" target="_blank" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 transition text-white rounded-xl inline-block text-xs uppercase tracking-wider font-bold">Open External Map</a>
                    </div>`;
                }
            }));
        });
    </script>
    
    <div x-show="isRouteModalOpen" x-cloak
        class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-3xl w-full shadow-2xl overflow-hidden flex flex-col h-[70vh]"
            @click.away="isRouteModalOpen = false"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95" 
            x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100 mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-map-location-dot mr-1 text-blue-600"></i> Interactive Route Verification
                </span>
                <button type="button" @click="isRouteModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
            
            <div class="flex flex-wrap gap-2 items-center px-4 py-2 text-xs text-slate-600 bg-slate-50 rounded-xl mb-2 font-medium border border-slate-100">
                <span x-text="routeOrigin" class="font-bold"></span>
                <i class="fa-solid fa-arrow-right-long text-slate-400"></i>
                <span x-text="routeDestination" class="font-bold"></span>
            </div>

            <div id="map-container" class="flex-1 bg-slate-100/50 rounded-xl overflow-hidden shadow-inner flex items-center justify-center relative">
                <!-- Map injects here -->
                <div class="animate-pulse flex flex-col items-center justify-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                    <span class="text-xs font-bold tracking-wider uppercase">Loading Route Map...</span>
                </div>
            </div>
        </div>
    </div>
</div>
