export type PilotInquiry = {
  name: string
  email: string
  message: string
}

export type SubmitOutcome =
  | { ok: true }
  | { ok: false; reason: 'unconfigured' | 'server-error' | 'network-error' }

const CONTACT_ENDPOINT = import.meta.env.VITE_CONTACT_ENDPOINT as
  | string
  | undefined

export function isContactFormConfigured(): boolean {
  return typeof CONTACT_ENDPOINT === 'string' && CONTACT_ENDPOINT.trim() !== ''
}

export async function submitPilotInquiry(
  inquiry: PilotInquiry,
): Promise<SubmitOutcome> {
  if (!isContactFormConfigured()) {
    return { ok: false, reason: 'unconfigured' }
  }
  try {
    const response = await fetch(CONTACT_ENDPOINT as string, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(inquiry),
    })
    if (!response.ok) {
      return { ok: false, reason: 'server-error' }
    }
    return { ok: true }
  } catch {
    return { ok: false, reason: 'network-error' }
  }
}
