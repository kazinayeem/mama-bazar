import { useEffect } from 'react'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { setTheme } from '../store/slices/uiSlice'

export const useThemeSync = () => {
  const theme = useAppSelector((state) => state.ui.theme)
  const dispatch = useAppDispatch()

  // The site is light-only: purge any persisted dark preference up front.
  useEffect(() => {
    const stored = window.localStorage.getItem('technest-theme')
    if (stored && stored !== 'light') {
      dispatch(setTheme('light'))
    }
  }, [dispatch, theme])

  useEffect(() => {
    document.documentElement.classList.remove('dark')
    window.localStorage.setItem('technest-theme', 'light')
  }, [theme])
}
