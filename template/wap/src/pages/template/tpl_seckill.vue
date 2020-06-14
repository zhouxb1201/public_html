<template>
  <van-cell-group :class="item.id">
    <van-cell :title="item.params.title" value-class="time">
      <template v-if="filterList.length>0">
        <span class="text">{{timeText}}</span>
        <CountDown :time="timestamp" @callback="onCallback" done-text="00:00:00">
          <div class="time-box">
            <span>{%d}</span>
            <i>:</i>
            <span>{%h}</span>
            <i>:</i>
            <span>{%m}</span>
            <i>:</i>
            <span>{%s}</span>
          </div>
        </CountDown>
      </template>
    </van-cell>
    <van-cell>
      <router-link tag="div" class="list" v-if="filterList.length>0" to="/seckill/list">
        <div class="item" v-for="(item,index) in filterList" :key="index">
          <div class="img">
            <span class="tag">秒杀</span>
            <img v-lazy="item.pic_cover" :key="item.pic_cover" pic-type="square" />
          </div>
          <div class="info">
            <div class="name text-nowrap">{{item.goods_name}}</div>
            <div class="price">
              <span class="sale">{{item.seckill_price | yuan}}</span>
            </div>
          </div>
        </div>
      </router-link>
      <div v-else class="empty">{{emptyText}}</div>
    </van-cell>
  </van-cell-group>
</template>

<script>
import CountDown from "@/components/CountDown";
import { GET_CUSTOMSECKILL } from "@/api/seckill";
export default {
  name: "tpl_seckill",
  data() {
    return {
      list: [],
      seckill_going_status: null,
      end_time: 0,
      begin_time: 0,
      seckill_time: null
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    filterList() {
      let list = [];
      list = this.list.filter((e, i) => i < 3);
      return list;
    },
    timestamp() {
      let time = 0;
      if (this.seckill_going_status == "going") {
        // 进行中
        time = parseInt(this.end_time) * 1000;
      } else if (this.seckill_going_status == "unstart") {
        // 未开始
        time = parseInt(this.begin_time) * 1000;
      }
      return time;
    },
    timeText() {
      let text = "";
      if (this.seckill_going_status == "going") {
        // 进行中
        text = this.seckill_time + "点场";
      } else if (this.seckill_going_status == "unstart") {
        // 未开始
        text = "距开始";
      }
      return text;
    },
    emptyText() {
      let text = "";
      if (!this.$store.state.config.addons.seckill) {
        text = "秒杀应用未开启";
      } else if (this.list.length == 0) {
        text = "暂无秒杀商品";
      }
      return text;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      GET_CUSTOMSECKILL(this.item.params.goodssort).then(({ data }) => {
        this.list = data.goods_list;
        if (data.goods_list.length > 0) {
          this.seckill_going_status = data.seckill_going_status;
          this.end_time = data.end_time;
          this.seckill_time = data.seckill_time;
          this.begin_time = data.begin_time;
        }
      });
    },
    onCallback() {
      this.loadData();
    }
  },
  components: {
    CountDown
  }
};
</script>

<style scoped>
.time {
  color: #474758;
  font-size: 12px;
  display: flex;
  justify-content: flex-end;
  white-space: nowrap;
  overflow: initial;
}

.time .text {
  padding: 0 10px;
}

.time-box span {
  color: #ffffff;
  background: #474758;
  padding: 0px 4px;
  border-radius: 2px;
}

.time-box i {
  font-style: normal;
}

.list {
  overflow: hidden;
  display: flex;
  margin: 0 -4px;
}

.item {
  position: relative;
  width: calc(33.33333% - 8px);
  float: left;
  margin: 4px;
}

.item .img {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
  border-radius: 8px;
  position: relative;
}

.item .img .tag {
  position: absolute;
  top: 0;
  left: 0;
  background: #f23030;
  color: #fff;
  font-size: 12px;
  width: 26px;
  text-align: center;
  line-height: 1.4;
  padding: 4px;
}

.item .img img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.item .info {
  font-size: 12px;
  line-height: 1.2;
}

.item .info .name {
  padding: 6px 0;
}

.item .info .price {
  overflow: hidden;
  display: flex;
}

.item .info .price .sale {
  font-weight: 800;
  color: #ff454e;
}

.item .info .price .market {
  color: #999999;
  font-weight: 400;
  margin-left: 6px;
  font-size: 0.8em;
  text-decoration: line-through;
}
</style>

