import { useEffect } from 'react'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { setTheme } from '../store/slices/uiSlice'

export const useThemeSync = () => {
  const theme = useAppSelector((state) => state.ui.theme)
  const dispatch = useAppDispatch()

  useEffect(() => {
    const stored = window.localStorage.getItem('technest-theme') as 'light' | 'dark' | null
    if (stored && stored !== theme) {
      dispatch(setTheme(stored))
    }
  }, [dispatch, theme])

  useEffect(() => {
    const root = document.documentElement
    if (theme === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }
    window.localStorage.setItem('technest-theme', theme)
  }, [theme])
}
