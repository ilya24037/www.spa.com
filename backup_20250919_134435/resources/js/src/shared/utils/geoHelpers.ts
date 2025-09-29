/**
 * A?><>30B5;L=K5 DC=:F88 4;O @01>BK A :>>@48=0B0<8 <0AB5@>2
 * >445@6820NB @07=K5 D>@<0BK API (lat/lng, latitude/longitude)
 */
import { Master } from '@/src/entities/master/model/types'

/**
 * >;CG8BL :>>@48=0BK <0AB5@0 87 @07=KE ?>;59
 * @param master - >1J5:B <0AB5@0
 * @returns <0AA82 [latitude, longitude] 8;8 null 5A;8 :>>@48=0B =5B
 */
export function getMasterCoordinates(master: Master): [number, number] | null {
  // @>25@O5< 2A5 2>7<>6=K5 D>@<0BK :>>@48=0B
  const lat = master.lat ?? master.latitude
  const lng = master.lng ?? master.longitude
  
  if (typeof lat === 'number' && typeof lng === 'number') {
    return [lat, lng]
  }
  
  return null
}

/**
 * @>25@8BL 5ABL ;8 C <0AB5@0 20;84=K5 :>>@48=0BK
 * @param master - >1J5:B <0AB5@0
 * @returns true 5A;8 :>>@48=0BK 5ABL 8 20;84=K
 */
export function hasMasterCoordinates(master: Master): boolean {
  const coords = getMasterCoordinates(master)
  return coords !== null
}

/**
 * >;CG8BL B>;L:> <0AB5@>2 A 20;84=K<8 :>>@48=0B0<8
 * @param masters - <0AA82 <0AB5@>2
 * @returns >BD8;LB@>20==K9 <0AA82 <0AB5@>2 A :>>@48=0B0<8
 */
export function getMastersWithCoordinates(masters: Master[]): Master[] {
  return masters.filter(hasMasterCoordinates)
}

/**
 * !>740BL >1J5:B :>>@48=0B 4;O Yandex Maps API
 * @param master - >1J5:B <0AB5@0
 * @returns >1J5:B A :>>@48=0B0<8 2 D>@<0B5 {lat, lng} 8;8 null
 */
export function getMasterCoordsForYandex(master: Master): { lat: number; lng: number } | null {
  const coords = getMasterCoordinates(master)
  
  if (coords) {
    return {
      lat: coords[0],
      lng: coords[1]
    }
  }
  
  return null
}