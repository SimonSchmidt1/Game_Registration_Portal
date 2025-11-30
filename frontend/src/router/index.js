import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AddGameView from '../views/AddGameView.vue' // Legacy
import AddProjectView from '../views/AddProjectView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import GameView from '../views/GameView.vue' // Legacy
import ProjectView from '../views/ProjectView.vue'
import TeamView from '../views/TeamView.vue'
import VerifyEmail from '../views/VerifyEmail.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'

const routes = [
  { path: '/', name: 'Home', component: HomeView },
  { path: '/login', name: 'Login', component: LoginView },
  { path: '/register', name: 'Register', component: RegisterView },
  { path: '/verify-email', name: 'VerifyEmail', component: VerifyEmail },
  { path: '/forgot-password', name: 'ForgotPassword', component: ForgotPasswordView },
  { path: '/reset-password', name: 'ResetPassword', component: ResetPasswordView },
  { path: '/add-project', name: 'AddProject', component: AddProjectView, meta: { requiresAuth: true } },
  { path: '/edit-project/:id', name: 'EditProject', component: AddProjectView, meta: { requiresAuth: true } },
  { path: '/project/:id', name: 'ProjectDetail', component: ProjectView },
  { path: '/team/:id', name: 'TeamDetail', component: TeamView },
  { path: '/add', name: 'AddGame', component: AddGameView, meta: { requiresAuth: true } }, // Backward compatibility
  { path: '/game/:id', name: 'GameDetail', component: GameView }, // Backward compatibility
]
const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')

  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router
