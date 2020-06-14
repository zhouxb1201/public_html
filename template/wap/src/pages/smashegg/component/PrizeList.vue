<template>
  <div>
    <div class="prize-wrap">
      <h3 class="prize-title">砸金蛋中奖记录</h3>
      <div class="prize-list-wrap" ref="prize">
        <ul class="item" ref="prizeFirstUl">
          <li v-for="(item,index) in listData" :key="index">
            <span class="title">{{item.user_tel | telvague}}</span>
            <span class="date">{{item.term_name}}</span>
          </li>
        </ul>
        <ul>
          <li v-for="(v,k) in copyHtml" :key="k">
            <span class="title">{{v.user_tel | telvague}}</span>
            <span class="date">{{v.term_name}}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { GET_PRIZERECORDS } from "@/api/smashegg";
export default {
  data() {
    return {
      listData: [],
      params: {
        page_index: 1,
        page_size: 50
      },
      nowTime: null, //定时器标识
      copyHtml: null
    };
  },
  watch: {},
  filters: {
    telvague(val) {
      return val ? val.substr(0, 3) + "****" + val.substr(7) : "匿名";
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      let $this = this;
      $this.params.smash_egg_id = $this.$route.params.smasheggid
        ? $this.$route.params.smasheggid
        : null;
      GET_PRIZERECORDS($this.params).then(res => {
        $this.listData = res.data.data;
        $this.moveTop();
      });
    },
    closeInterval() {
      clearInterval(this.nowTime); //页面关闭清除定时器
      this.nowTime = null; //清除定时器标识
    },
    moveTop() {
      let top = null;
      if (this.listData.length <= 6) {
        //如果数据无6条以上不执行无缝滚动
        return false;
      }
      this.copyHtml = this.listData;
      let prize = this.$refs.prize;
      this.nowTime = setInterval(() => {
        if (prize.scrollTop - this.$refs.prizeFirstUl.scrollHeight <= 0) {
          prize.scrollTop++; //滚动条滚动加1
        } else {
          prize.scrollTop = 0; //滚动条回到最顶端
        }
      }, 50);
    }
  }
};
</script>
<style scoped>
.prize-wrap {
  margin: 20px;
  background-color: #fff;
  border-radius: 10px;
}
.prize-title {
  text-align: center;
  font-size: 14px;
  color: #e84d2f;
  padding: 18px;
}
.prize-list-wrap {
  overflow: hidden;
  padding: 6px 20px;
  color: #666;
  height: 178px;
}
.prize-list-wrap ul {
  position: relative;
  display: block;
  overflow: hidden;
}
.prize-list-wrap ul li {
  height: 28px;
  line-height: 28px;
  font-size: 13px;
  display: flex;
}
.prize-list-wrap ul li span {
  flex: 1;
}
.prize-list-wrap ul li span:last-child {
  text-align: right;
}
</style>

