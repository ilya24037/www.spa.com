// Main Footer component export
export { default as Footer } from './Footer.vue'

// Footer subcomponents export
export { default as FooterSectionComponent } from './components/FooterSection.vue'
export { default as SocialIcons } from './components/SocialIcons.vue'
export { default as AppDownload } from './components/AppDownload.vue'
export { default as FooterIcon } from './components/FooterIcon.vue'
export { default as SocialIcon } from './components/SocialIcon.vue'

// Footer configuration and types export
export type {
  FooterConfig,
  FooterSection as FooterSectionType,
  FooterLink,
  SocialLink,
  AppStore
} from './model/footer.config'

export {
  defaultFooterConfig,
  getFooterSectionById,
  getSortedFooterSections,
  filterVisibleLinks,
  isExternalLink
} from './model/footer.config'

// Composable for Footer usage
export { useFooter } from './composables/useFooter'