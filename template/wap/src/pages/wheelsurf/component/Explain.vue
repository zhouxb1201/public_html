<template>
  <div class="popup" v-if="isShow">
    <div class="popup-explain-wrap">
      <div class="explain-content">
        <div class="prize-title">
          <img :src="$BASEIMGPATH + 'prize_title.png'">
          <span>活动奖品</span>
        </div>
        <div class="prize-table">
          <ul class="table-th">
            <li>
              <span class="tal">奖项</span>
              <span>奖品名称</span>
              <span class="tar">数量</span>
            </li>
          </ul>
          <ul>
            <li v-for="(item,index) in info.prize" :key="index">
              <span class="tal">{{item.term_name}}</span>
              <span>{{item.prize_name}}</span>
              <span class="tar">{{item.num}}</span>
            </li>
          </ul>
        </div>

        <div class="prize-title mt20">
          <img :src="$BASEIMGPATH + 'prize_title.png'">
          <span>活动时间</span>
        </div>
        <p class="time">{{info.start_time | formatDate("s")}} ~ {{info.end_time | formatDate("s")}}</p>

        <div class="prize-title" v-if="info.desc">
          <img :src="$BASEIMGPATH + 'prize_title.png'">
          <span>活动说明</span>
        </div>
        <p class="time" v-if="info.desc">{{info.desc}}</p>
      </div>
      <!---知道了-->
      <div class="explain-bottom">
        <img :src="$BASEIMGPATH + 'arcs.png'" class="pic-arcs">
        <div class="bottom-wrap">
          <van-button type="danger" size="small" @click.native="close">知道了</van-button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { GET_WHEELSURFINFO } from "@/api/wheelsurf";
export default {
  props: {
    //是否弹出
    isShow: {
      type: Boolean,
      default: false
    },
    id: [Number, String]
  },
  data() {
    return {
      info: {}
    };
  },
  watch: {
    isShow(val) {
      if (val === true) {
        document.querySelector("html").style.overflow = "hidden";
      } else {
        document.querySelector("html").style.overflow = "";
      }
    }
  },
  mounted() {
    GET_WHEELSURFINFO(this.id).then(({ data }) => {
      this.info = data;
    });
  },
  methods: {
    close() {
      this.$emit("close");
    }
  }
};
</script>
<style scoped>
.popup {
  background-color: rgba(0, 0, 0, 0.8);
  position: fixed;
  top: 0;
  left: 0;
  transform: translate3d(0, 0, 0);
  -webkit-transform: translate3d(0, 0, 0);
  height: 100%;
  width: 100%;
  z-index: 2001;
  transition: all 0.2s;
}
.popup-explain-wrap {
  position: relative;
  width: 90%;
  left: 50%;
  top: 50%;
  background: linear-gradient(top, #e94e2f, #95100d);
  background: -webkit-gradient(
    linear,
    left top,
    left bottom,
    from(#e94e2f),
    to(#95100d)
  );
  border-radius: 8px;
  overflow: hidden;
  transform: translate(-50%, -50%);
}
.explain-content {
  background-color: #fff;
  border-radius: 8px;
  padding: 20px 15px 80px;
  margin: 20px 15px;
}
.prize-title {
  width: 98px;
  height: 34px;
  position: relative;
  overflow: hidden;
}
.prize-title img {
  width: 100%;
  height: auto;
}
.prize-title span {
  color: #e94e2f;
  text-align: center;
  position: absolute;
  top: 16px;
  right: 8px;
  font-size: 12px;
}
.prize-table {
  position: relative;
  overflow: hidden;
  margin: 0 15px;
}
.prize-table ul li,
.table-th li {
  height: 30px;
  line-height: 30px;
  text-align: center;
  display: flex;
}
.prize-table ul:last-child li {
  color: #666666;
}
.prize-table ul:last-child {
  max-height: 150px;
  overflow-y: auto;
}
.table-th {
  position: relative;
}
.table-th li {
  color: #e84d2f;
  font-size: 15px;
}
.table-th:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 1px;
  background-color: #cccccc;
  transform: scaleY(0.5);
}
.prize-table ul li span:nth-child(2) {
  width: 50%;
}
.prize-table ul li span {
  display: inline-block;
  width: 25%;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.tal {
  text-align: left;
}
.tar {
  text-align: right;
}
.time {
  margin-left: 20px;
  color: #666666;
  font-size: 12px;
  padding: 10px 0px;
}
.explain-bottom {
  width: 100%;
  position: absolute;
  z-index: 100;
  bottom: 0;
  left: 0;
  line-height: 0;
}
.pic-arcs {
  width: 100%;
  display: block;
}
.explain-bottom .bottom-wrap {
  position: relative;
  width: 100%;
  background-color: #e64a2d;
  padding: 0px 10px 10px;
}
.explain-bottom .bottom-wrap >>> .van-button {
  margin: auto;
  background-color: #f7d724;
  color: #e54125;
  border-radius: 20px;
  font-size: 15px;
  width: 60%;
  margin-left: 20%;
  height: 36px;
}
.mt20 {
  margin-top: 20px;
}
</style>