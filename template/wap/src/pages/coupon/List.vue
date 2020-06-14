<template>
  <div class="coupon-list bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'coupon', 
        message: '没有相关优惠券', 
        showFoot: true,
        top:$store.state.isWeixin?46:90,
        btnLink: '/coupon/centre', 
        btnText: '去领券',
      }"
      @load="loadList"
    >
      <div class="item" v-for="(item,index) in list" :key="index">
        <div class="info">
          <div class="money" :class="item.state == 1 ? 'use' : ''">
            <div class="num letter-price">{{genreTxt(item)}}</div>
            <div v-if="item.coupon_genre == 1">无门槛</div>
            <div v-else>满{{item.at_least | toNumber}}可用</div>
          </div>
          <div class="text">
            <div class="name">{{item.show_name}}</div>
            <div class="time">{{item.start_time | formatDate}} ~ {{item.end_time | formatDate}}</div>
            <router-link class="a-link fs-12" :to="'/coupon/detail/'+item.coupon_type_id">详情 ▶</router-link>
          </div>
        </div>
        <div class="icon-bg" v-if="item.state != 1">
          <van-icon :name="item.state == 2 ? 'v-icon-coupon-use' : 'v-icon-overdue'" />
        </div>
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import { GET_COUPONLIST } from "@/api/coupon";
import { list } from "@/mixins";
export default sfc({
  name: "coupon-list",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "未使用",
          status: 1
        },
        {
          name: "已使用",
          status: 2
        },
        {
          name: "已过期",
          status: 3
        }
      ],

      params: {
        page_index: 1,
        state: 1
      }
    };
  },
  filters: {
    toNumber(value) {
      return parseFloat(value) ? parseFloat(value) : 0;
    }
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const status = $this.tabs[index].status;
      $this.params.state = status;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_COUPONLIST($this.params)
        .then(({ data }) => {
          let list = data.list;
          $this.pushToList(list, data.total_page, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    genreTxt({ coupon_genre, money, discount }) {
      let text = "";
      if (coupon_genre == 3) {
        text = parseFloat(discount) + "折";
      } else {
        text = "¥ " + parseFloat(money);
      }
      return text;
    }
  },
  components: {
    HeadTab
  }
});
</script>

<style scoped>
.list .item {
  display: flex;
  align-items: center;
  background: #ffffff;
  border-radius: 4px;
  margin: 15px;
  padding: 20px 15px;
  position: relative;
  overflow: hidden;
}

.list .item::after,
.list .item::before {
  content: "";
  display: block;
  position: absolute;
  width: 16px;
  height: 16px;
  background: #f8f8f8;
  border-radius: 50%;
  top: 50%;
  margin-top: -8px;
}

.list .item::after {
  right: -8px;
}

.list .item::before {
  left: -8px;
}

.list .item .info {
  flex: 1;
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  height: 50px;
}

.list .item .info .money {
  width: 90px;
  color: #606266;
}

.list .item .info .money.use {
  color: #ffab33;
}

.list .item .info .money .num {
  font-size: 22px;
  margin-bottom: 4px;
  display: inline-block;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.list .item .info .text {
  flex: 1;
}

.list .item .info .text .name {
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 8px;
  line-height: 1.4;
}

.list .item .info .text .time {
  font-size: 12px;
  color: #909399;
}

.list .item .icon-bg {
  position: absolute;
  z-index: 9;
  right: 10px;
  font-size: 50px;
}

.list .item .icon-bg >>> .van-icon {
  display: block;
}
</style>
