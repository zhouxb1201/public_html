<template>
  <div class="channel-achieve bg-f8">
    <Navbar />
    <div>
      <van-cell :border="false">
        <van-row type="flex" justify="space-around" class="panel-box">
          <van-col span="2" class="icon-box e-handle" @click.native="onChangeMonth('pre')">
            <van-icon name="arrow-left" />
          </van-col>
          <van-col span="20" class="panel-main">
            <div>
              <div>本月业绩({{params.date_time}})</div>
              <div class="num">{{info.sale_money ? info.sale_money : 0}}</div>
            </div>
          </van-col>
          <van-col span="2" class="icon-box e-handle" @click.native="onChangeMonth('next')">
            <van-icon name="arrow" />
          </van-col>
        </van-row>
      </van-cell>
      <CellPanelGroup :show-head="false" :items="cellPanelItems" />
    </div>
    <van-cell title="团队业绩" :border="false" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{top:$store.state.isWeixin?244:290,message: '没有相关团队业绩'}"
      @load="loadList"
    >
      <van-cell-group class="item" v-for="(item,index) in list" :key="index">
        <van-cell>
          <van-row type="flex" justify="space-around">
            <van-col span="18">
              <div>{{item.name}}</div>
              <van-row type="flex" class="fs-12 text-regular">
                <van-col span="10" class="text-nowrap">
                  上级：
                  <span>{{item.up_channel_name}}</span>
                </van-col>
                <van-col span="14" class="text-nowrap">
                  推荐人：
                  <span>{{item.referee_name}}</span>
                </van-col>
              </van-row>
              <van-row type="flex" class="fs-12 text-regular">
                <van-col span="24" class="text-nowrap">
                  等级：
                  <span>{{item.grade_name}}</span>
                </van-col>
              </van-row>
            </van-col>
            <van-col span="6" class="item-right-box">
              <div class="letter-price">{{item.sale_money | yuan}}</div>
              <div>本月业绩</div>
            </van-col>
          </van-row>
        </van-cell>
        <van-cell>
          <div class="fs-12 text-regular item-foot-box">
            买入：
            <span>{{item.my_purchase_money}}</span> 利润：
            <span>{{item.my_profit}}</span> 奖金：
            <span>{{item.my_bonus}}</span>
          </div>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import CellPanelGroup from "@/components/CellPanelGroup";
import { GET_ACHIEVELIST } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-achieve",
  data() {
    const getFullYear = new Date().getFullYear();
    const getMonth = new Date().getMonth() + 1;
    return {
      info: "",
      currFullYear: getFullYear,
      currMonth: getMonth,
      params: {
        page_index: 1,
        page_size: 10,
        date_time: getFullYear + "-" + getMonth
      }
    };
  },
  mixins: [list],
  computed: {
    yearMonth() {
      return new Date().getFullYear() + "-" + (new Date().getMonth() + 1);
    },
    cellPanelItems() {
      const info = this.info;
      return [
        {
          title: "买入",
          text: info.my_purchase_money ? info.my_purchase_money : 0
        },
        {
          title: "利润",
          text: info.my_profit ? info.my_profit : 0
        },
        {
          title: "奖金",
          text: info.my_bonus ? info.my_bonus : 0
        }
      ];
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    onChangeMonth(action) {
      const $this = this;
      if (action == "pre") {
        $this.currMonth = $this.currMonth == 1 ? 12 : $this.currMonth - 1;
        $this.currFullYear =
          $this.currMonth == 12 ? $this.currFullYear - 1 : $this.currFullYear;
      } else if (action == "next") {
        $this.currFullYear =
          $this.currMonth == 12 ? $this.currFullYear + 1 : $this.currFullYear;
        $this.currMonth = $this.currMonth == 12 ? 1 : $this.currMonth + 1;
      }
      $this.params.date_time =
        $this.currFullYear +
        "-" +
        ($this.currMonth < 10 ? "0" + $this.currMonth : $this.currMonth);
      // console.log($this.params.date_time);
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_ACHIEVELIST($this.params)
        .then(({ data }) => {
          let list = data.data.down_channel ? data.data.down_channel : [];
          $this.info = data.data.my_performance;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    CellPanelGroup
  }
});
</script>

<style scoped>
.panel-box {
  align-items: center;
  height: 80px;
}

.panel-box .icon-box {
  display: flex;
  align-items: center;
  font-size: 40px;
  color: #999;
  width: 40px;
  height: 40px;
  justify-content: center;
}

.panel-box .panel-main {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.num {
  font-size: 30px;
  margin-top: 10px;
  color: #ff454e;
}

.item {
  margin-bottom: 10px;
}

.item-right-box {
  display: flex;
  flex-flow: column;
  align-self: center;
  text-align: center;
  border-left: 1px solid #ddd;
}

.item-foot-box span {
  padding-right: 10px;
}
</style>
