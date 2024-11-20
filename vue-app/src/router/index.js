import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      beforeEnter: (to, from, next) => {
        const { uri } = to.query;
        if (uri != null && uri != '/') {
          next(false);
          router.push(uri);
        } else {
          next();
        }
      }
    },
    {
      path: '/oidc',
      name: 'oidc',
      component: () => import('../views/OidcView.vue')
    },
    {
      path: '/tour/:tourId',
      name: 'tour',
      component: () => import('../views/TourView.vue')
    },
    {
      path: '/new_tour/',
      name: 'newTour',
      component: () => import('../views/TourView.vue')
    },
    {
      path: '/tour/:tourId/add_spot',
      name: 'addSpot',
      component: () => import('../views/SpotView.vue')
    },
    {
      path: '/spot/:spotId',
      name: 'editSpot',
      component: () => import('../views/SpotView.vue'),
    }
  ]
})

export default router
