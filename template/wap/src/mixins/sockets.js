// socket.io
import { mapGetters, mapMutations, mapState, mapActions } from 'vuex';
import Notify from '@/components/notify';
const io = require('../../static/js/socket.io');
let socketIO = null;
let reconnectNum = 0; //重连次数
let maxReconnectNum = 20;

const socket = {
  data() {
    return {
    };
  },

  watch: {
    'info.uid'(e) {
      e && this.socketInit();
    },
    'reconnect'(e) {
      console.log(e);
      this.socketInit();
    }
  },

  computed: {
    ...mapGetters(['config']),
    ...mapState({
      reconnect: state => state.message.reconnect,
      info: state => state.member.info,
      addons: state => state.config.addons
    })
  },

  methods: {
    ...mapActions(['socketChatMessage']),
    ...mapMutations(['socketConnect', 'socketComQuestion', 'socketIsClose', 'socketRelink', 'socketLinkByKF']),
    // 初始化socketIO
    socketInit() {
      const $this = this;
      const uri = $this.config.qlkefu_domain_port;
      if (uri && $this.addons.qlkefu) {
        $this.$nextTick(() => {
          socketIO = io(uri);
          socketIO.on("connect", (connect) => {
            $this.socketConnect(connect);
            $this.socketLogin();
            socketIO.on('chatMessage', (data) => {
              $this.socketChatMessage(data);
            })
            socketIO.on('comQuestion', (data) => {
              $this.socketComQuestion(data);
            })
            socketIO.on('hello', (data) => {
              $this.socketHello(data);
            })
            socketIO.on('isClose', (data) => {
              $this.socketIsClose(data);
            })
            socketIO.on('relink', (data) => {
              $this.socketRelink(data);
            })
            socketIO.on('linkByKF', (data) => {
              $this.socketLinkByKF(data);
            })
          });
          socketIO.on('disconnect', (data) => {
            reconnectNum += 1;
            console.log("链接已断开,断开次数", reconnectNum);
            reconnectNum < maxReconnectNum && socketIO.emit('reconnect', true);
          })
          socketIO.on('reconnect', (data) => {
            console.log("重新连接socket,重连次数", reconnectNum);
            reconnectNum < maxReconnectNum && socketIO.emit('connect', true);
          })
          socketIO.on('connect_failed', (data) => {
            console.log("连接失败", data);
          })
          socketIO.on('connect_error', (data) => {
            console.log("连接错误", data);
            socketIO.close();
            // Notify({
            //   message: 'socket连接出错！',
            //   background: 'red',
            //   duration: 3000
            // });
          })
        })
      }
    },
    // 客服登录
    socketLogin() {
      if (!this.info) return;
      const { uid, username, member_img } = this.info;
      const params = {
        data: {
          customer_id: uid,
          customer_name: username,
          customer_avatar: member_img,
        }
      }
      socketIO.emit("userIn", JSON.stringify(params), function (res) {
        const { code, data, msg } = JSON.parse(res)
        switch (code) {
          case 0:
            console.log('客服接入成功', msg)
            break;
          case 400:
            console.log('接入失败，请重新接入', msg)
            break;
        }
      });
    },
  }

};

export default socket
