import { ref } from 'vue'
import type { Coordinates } from '../types'
import { useMapState } from './useMapState'
import { useMapModes } from './useMapModes'
import { useMapEventHandlers } from './useMapEventHandlers'
import { useGeolocation } from './useGeolocation'
import { useAddressGeocoding } from './useAddressGeocoding'

export function useMapController(props: any, emit: any) {
  // Refs
  const mapBaseRef = ref<any>()
  const mapInstance = ref<any>(null)
  
  // Tooltip state
  const tooltip = {
    visible: ref(false),
    address: ref(''),
    position: ref({ x: 0, y: 0 })
  }
  
  // Composables
  const mapState = useMapState()
  const mapModes = useMapModes({ 
    mode: props.mode, 
    modelValue: props.modelValue 
  })
  const eventHandlers = useMapEventHandlers(emit)
  const geolocation = useGeolocation()
  const { getAddressFromCoords, searchAndCenterOnAddress } = useAddressGeocoding()
  
  // Event Handlers
  const handleMapReady = (map: any) => {
    mapInstance.value = map
    mapState.setReady()
    
    if (mapModes.isSingleMode.value) {
      const coords = mapModes.setupSingleMode(props.modelValue)
      if (coords && mapBaseRef.value) {
        mapBaseRef.value.setCenter(coords)
      }
    }
    
    eventHandlers.handleMapReady(map)
  }
  
  const handleCenterChange = (center: Coordinates) => {
    if (mapModes.isSingleMode.value) {
      const formatted = mapModes.handleSingleModeChange(center)
      emit('update:modelValue', formatted)
      emit('marker-moved', center)
      
      if (props.showAddressTooltipOnHover) {
        updateAddressFromCoords(center)
      }
    }
    
    eventHandlers.handleCenterChange(center)
  }
  
  const handleMapClick = (coords: Coordinates) => {
    if (props.mode === 'single' && props.draggable && mapBaseRef.value) {
      mapBaseRef.value.setCenter(coords)
    }
    eventHandlers.handleMapClick(coords)
  }
  
  const handleMapError = (error: string) => {
    mapState.setError(error)
    eventHandlers.handleMapError(error)
  }
  
  const handleRetry = () => {
    mapState.reset()
  }
  
  const handleGeolocationClick = async () => {
    if (mapInstance.value) {
      await geolocation.centerOnUserLocation(mapInstance.value)
      
      if (geolocation.userLocation.value) {
        const address = await getAddressFromCoords(geolocation.userLocation.value)
        emit('address-found', address || '', geolocation.userLocation.value)
      }
    }
  }
  
  const handleMarkerHover = async () => {
    const center = eventHandlers.lastCenter.value
    if (center) {
      const address = await getAddressFromCoords(center)
      if (address) {
        tooltip.address.value = address
        emit('marker-address-hover', address)
      }
    }
  }
  
  // Address update with debounce
  let addressUpdateTimeout: NodeJS.Timeout | null = null
  const updateAddressFromCoords = async (coords: Coordinates) => {
    if (addressUpdateTimeout) {
      clearTimeout(addressUpdateTimeout)
    }
    
    addressUpdateTimeout = setTimeout(async () => {
      const address = await getAddressFromCoords(coords)
      if (address) {
        tooltip.address.value = address
      }
    }, 500)
  }
  
  // Public methods
  const searchAddress = async (address: string) => {
    if (mapInstance.value) {
      const success = await searchAndCenterOnAddress(address, mapInstance.value)
      if (!success) {
        emit('search-error', 'Адрес не найден')
      }
    }
  }
  
  const setCoordinates = (coords: Coordinates) => {
    if (mapBaseRef.value) {
      mapBaseRef.value.setCenter(coords)
    }
  }
  
  return {
    // Refs
    mapBaseRef,
    mapInstance,
    
    // State
    mapState,
    geolocation,
    tooltip,
    
    // Handlers
    handleMapReady,
    handleCenterChange,
    handleMapClick,
    handleMapError,
    handleRetry,
    handleGeolocationClick,
    handleMarkerHover,
    
    // Public methods
    searchAddress,
    setCoordinates
  }
}