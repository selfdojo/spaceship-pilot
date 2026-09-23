export function Home() {
  return (
    <div>
      <h1>Spaceship hosting pilot</h1>
      <p>
        Throwaway app used to validate Spaceship as a static React/Vite SPA
        host before committing real sites to it. See the README in this repo
        for the full test checklist.
      </p>
      <p>Build marker: {import.meta.env.VITE_BUILD_MARKER ?? 'not set'}</p>
    </div>
  )
}
