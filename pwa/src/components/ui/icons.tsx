import type { ReactNode, SVGProps } from 'react'

type IconProps = SVGProps<SVGSVGElement>

function Icon({ children, ...props }: IconProps & { children: ReactNode }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.8}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      {...props}
    >
      {children}
    </svg>
  )
}

export function TablesIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <rect x="3" y="3" width="7" height="7" rx="1.5" />
      <rect x="14" y="3" width="7" height="7" rx="1.5" />
      <rect x="3" y="14" width="7" height="7" rx="1.5" />
      <rect x="14" y="14" width="7" height="7" rx="1.5" />
    </Icon>
  )
}

export function HomeIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M4 10.5L12 4l8 6.5" />
      <path d="M6 10v10h12V10" />
      <path d="M10 20v-5h4v5" />
    </Icon>
  )
}

export function PrinterIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M7 8V4h10v4" />
      <rect x="4" y="8" width="16" height="8" rx="2" />
      <path d="M7 14h10v6H7z" />
    </Icon>
  )
}

export function LogoutIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
      <path d="M16 17l5-5-5-5" />
      <path d="M21 12H9" />
    </Icon>
  )
}

export function CheckIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M20 6L9 17l-5-5" />
    </Icon>
  )
}

export function MinusIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M5 12h14" />
    </Icon>
  )
}

export function StoreIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M3 9l1.5-5h15L21 9" />
      <path d="M4 9v11h16V9" />
      <path d="M9 20v-6h6v6" />
    </Icon>
  )
}

export function UserIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c0-4 4-6 8-6s8 2 8 6" />
    </Icon>
  )
}

export function DeviceIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <rect x="7" y="2" width="10" height="20" rx="2" />
      <path d="M11 18h2" />
    </Icon>
  )
}

export function WifiIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M5 12.55a11 11 0 0 1 14 0" />
      <path d="M8.5 16.03a6 6 0 0 1 7 0" />
      <circle cx="12" cy="19" r="1" />
    </Icon>
  )
}

export function WifiOffIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M2 2l20 20" />
      <path d="M5 12.55a11 11 0 0 1 5-2.4" />
      <path d="M14 6.35a11 11 0 0 1 5 6.2" />
      <path d="M8.5 16.03a6 6 0 0 1 7 0" />
      <circle cx="12" cy="19" r="1" />
    </Icon>
  )
}

export function ShieldIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z" />
    </Icon>
  )
}

export function ChevronRightIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M9 6l6 6-6 6" />
    </Icon>
  )
}

export function SparkIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 3v4" />
      <path d="M12 17v4" />
      <path d="M3 12h4" />
      <path d="M17 12h4" />
      <path d="M5.6 5.6l2.8 2.8" />
      <path d="M15.6 15.6l2.8 2.8" />
      <path d="M18.4 5.6l-2.8 2.8" />
      <path d="M8.4 15.6l-2.8 2.8" />
    </Icon>
  )
}

export function PlusIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 5v14" />
      <path d="M5 12h14" />
    </Icon>
  )
}

export function TrashIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M4 7h16" />
      <path d="M10 11v6" />
      <path d="M14 11v6" />
      <path d="M6 7l1 13h10l1-13" />
      <path d="M9 7V4h6v3" />
    </Icon>
  )
}

export function ChevronLeftIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M15 6l-6 6 6 6" />
    </Icon>
  )
}

export function CloseIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M6 6l12 12" />
      <path d="M18 6L6 18" />
    </Icon>
  )
}

export function RefreshIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M21 12a9 9 0 1 1-2.6-6.4" />
      <path d="M21 4v5h-5" />
    </Icon>
  )
}

export function NoteIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <rect x="4" y="3" width="16" height="18" rx="2" />
      <path d="M8 8h8" />
      <path d="M8 12h8" />
      <path d="M8 16h5" />
    </Icon>
  )
}

export function AlertIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 4l9 16H3z" />
      <path d="M12 10v4" />
      <circle cx="12" cy="17" r="0.5" fill="currentColor" />
    </Icon>
  )
}

export function SendIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M22 2L11 13" />
      <path d="M22 2l-7 20-4-9-9-4z" />
    </Icon>
  )
}

export function SearchIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-4.3-4.3" />
    </Icon>
  )
}

export function HistoryIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M3 12a9 9 0 1 0 3-6.7" />
      <path d="M3 4v5h5" />
      <path d="M12 8v4l3 2" />
    </Icon>
  )
}

export function DownloadIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 3v11" />
      <path d="M7 10l5 5 5-5" />
      <path d="M5 21h14" />
    </Icon>
  )
}

export function ShareIcon(props: IconProps) {
  return (
    <Icon {...props}>
      <path d="M12 3v12" />
      <path d="M8 7l4-4 4 4" />
      <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7" />
    </Icon>
  )
}
