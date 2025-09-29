/**
 * TypeScript определения для YMapsCore
 */

export interface YMapsConfig {
  apiKey?: string
  lang?: string
  version?: string
  coordorder?: string
  mode?: string
  load?: string
  ns?: string
}

export interface MapOptions {
  center: [number, number]
  zoom: number
  controls?: string[]
  behaviors?: string[]
  extra?: Record<string, any>
}

export interface MapData {
  id: string
  map: any
  container: HTMLElement
  options: MapOptions
  modules: Set<string>
  objects: Map<string, any>
}

declare class YMapsCore {
  config: YMapsConfig
  
  constructor(config?: YMapsConfig)
  
  loadAPI(): Promise<any>
  createMap(container: string | HTMLElement, options?: Partial<MapOptions>): Promise<any>
  destroyMap(mapOrId: string | any): void
  getMap(containerId: string): any | null
  isAPILoaded(): boolean
  getYMaps(): any | null
}

export default YMapsCore