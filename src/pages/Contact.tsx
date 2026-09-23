import { useState, type FormEvent } from 'react'
import { isContactFormConfigured, submitPilotInquiry } from '../lib/contactAdapter'

type Status = 'idle' | 'submitting' | 'success' | 'error'

export function Contact() {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [status, setStatus] = useState<Status>('idle')
  const [errorReason, setErrorReason] = useState<string | null>(null)

  const configured = isContactFormConfigured()

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setStatus('submitting')
    const outcome = await submitPilotInquiry({ name, email, message })
    if (outcome.ok) {
      setStatus('success')
      setName('')
      setEmail('')
      setMessage('')
    } else {
      setStatus('error')
      setErrorReason(outcome.reason)
    }
  }

  if (!configured) {
    return (
      <div>
        <h1>Contact</h1>
        <p>
          No <code>VITE_CONTACT_ENDPOINT</code> is set for this build. Set it
          to your Spaceship mail-relay endpoint and redeploy to test email
          delivery.
        </p>
      </div>
    )
  }

  if (status === 'success') {
    return (
      <div>
        <h1>Contact</h1>
        <p>Sent. Check the target inbox for delivery.</p>
      </div>
    )
  }

  return (
    <div>
      <h1>Contact</h1>
      <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '0.75rem', maxWidth: 360 }}>
        <label>
          Name
          <input value={name} onChange={(e) => setName(e.target.value)} required />
        </label>
        <label>
          Email
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        </label>
        <label>
          Message
          <textarea value={message} onChange={(e) => setMessage(e.target.value)} required />
        </label>
        <button type="submit" disabled={status === 'submitting'}>
          {status === 'submitting' ? 'Sending…' : 'Send'}
        </button>
        {status === 'error' && <p role="alert">Send failed: {errorReason}</p>}
      </form>
    </div>
  )
}
