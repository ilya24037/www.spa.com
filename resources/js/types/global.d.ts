// Global type declarations
declare global {
  interface Window {
    dayjs: any;
    route: any;
    [key: string]: any;
  }
  
  const route: (name?: string, params?: any, absolute?: boolean) => string;
  const dayjs: any;
}

export {};
