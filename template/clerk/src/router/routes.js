import centre from './centre'
import login from './login'
import account from './account'
import manage from './manage'
import order from './order'
import statistic from './statistic'
import verify from './verify'
import goods from './goods'

const routes = [
    ...centre,
    ...login,
    ...account,
    ...manage,
    ...order,
    ...statistic,
    ...verify,
    ...goods
]

export default routes